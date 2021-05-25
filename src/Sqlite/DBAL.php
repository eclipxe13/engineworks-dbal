<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace EngineWorks\DBAL\Sqlite;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL as AbstractDBAL;
use EngineWorks\DBAL\Traits\MethodSqlDatePartFormatAnsi;
use EngineWorks\DBAL\Traits\MethodSqlLike;
use EngineWorks\DBAL\Traits\MethodSqlLimit;
use Exception;
use RuntimeException;
use SQLite3;
use Throwable;

class DBAL extends AbstractDBAL
{
    use MethodSqlLike;
    use MethodSqlLimit;
    use MethodSqlDatePartFormatAnsi;

    /**
     * Contains the connection resource for SQLite3
     * @var SQLite3|null
     */
    protected $sqlite = null;

    public function connect(): bool
    {
        // disconnect, this will reset object properties
        $this->disconnect();
        // create the sqlite3 object without error reporting
        $level = error_reporting(0);
        try {
            $defaultFlags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
            $this->sqlite = new SQLite3(
                (string) $this->settings->get('filename', ':memory:'),
                ($this->settings->exists('flags')) ? (int) $this->settings->get('flags') : $defaultFlags
            );
        } catch (Throwable $ex) {
            $this->logger->info('-- Connection fail');
            $this->logger->error('Cannot create SQLite3 object: ' . $ex->getMessage());
            return false;
        } finally {
            error_reporting($level);
        }
        // OK, we are connected
        $this->logger->info('-- Connection success');
        $this->sqlite()->enableExceptions((bool) $this->settings->get('enable-exceptions', false));
        return true;
    }

    public function disconnect(): void
    {
        if ($this->isConnected()) {
            $this->logger->info('-- Disconnection');
            $this->sqlite()->close();
        }
        $this->transactionLevel = 0;
        $this->sqlite = null;
    }

    public function isConnected(): bool
    {
        return ($this->sqlite instanceof SQLite3);
    }

    public function lastInsertedID(): int
    {
        return $this->sqlite()->lastInsertRowID();
    }

    public function sqlString($variable): string
    {
        return str_replace(["\0", "'"], ['', "''"], $variable);
    }

    public function queryResult(string $query, array $overrideTypes = [])
    {
        /**
         * @scrutinizer ignore-unhandled
         * @noinspection PhpUsageOfSilenceOperatorInspection
         */
        $rslt = @$this->sqlite()->query($query);
        if (false !== $rslt) {
            return new Result($rslt, $overrideTypes);
        }
        return false;
    }

    protected function queryAffectedRows(string $query)
    {
        $this->logger->debug($query);
        try {
            $exec = $this->sqlite()->exec($query);
        } catch (Exception $ex) {
            $exec = false;
            $this->logger->info("-- Query fail with SQL: $query");
            $this->logger->error("FAIL: $query\nLast message:" . $ex->getMessage());
        }
        if (false !== $exec) {
            return max(0, $this->sqlite()->changes());
        }
        return false;
    }

    protected function getLastErrorMessage(): string
    {
        return '[' . $this->sqlite()->lastErrorCode() . '] ' . $this->sqlite()->lastErrorMsg();
    }

    public function sqlTableEscape(string $tableName, string $asTable = ''): string
    {
        return '"' . $tableName . '"' . (('' !== $asTable) ? ' AS ' . '"' . $asTable . '"' : '');
    }

    public function sqlFieldEscape(string $fieldName, string $asTable = ''): string
    {
        return '"' . $fieldName . '"' . (('' !== $asTable) ? ' AS ' . '"' . $asTable . '"' : '');
    }

    public function sqlConcatenate(...$strings): string
    {
        if (! count($strings)) {
            return $this->sqlQuote('', CommonTypes::TTEXT);
        }
        return implode(' || ', $strings);
    }

    public function sqlDatePart(string $part, string $expression): string
    {
        $format = $this->sqlDatePartFormatAnsi($part);
        return sprintf('STRFTIME(%s, %s)', $expression, $this->sqlQuote($format, self::TTEXT));
    }

    public function sqlIf(string $condition, string $truePart, string $falsePart): string
    {
        return 'CASE WHEN (' . $condition . ') THEN ' . $truePart . ' ELSE ' . $falsePart;
    }

    public function sqlRandomFunc(): string
    {
        return 'random()';
    }

    /**
     * @return SQLite3
     */
    private function sqlite()
    {
        if (null === $this->sqlite) {
            throw new RuntimeException('The current state of the connection is NULL');
        }
        return $this->sqlite;
    }
}
