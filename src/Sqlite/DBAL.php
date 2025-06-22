<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace EngineWorks\DBAL\Sqlite;

use EngineWorks\DBAL\Abstracts\BaseDBAL;
use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Traits\MethodSqlLike;
use EngineWorks\DBAL\Traits\MethodSqlLimit;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use SQLite3;
use SQLite3Result;
use Throwable;

class DBAL extends BaseDBAL
{
    use MethodSqlLike;
    use MethodSqlLimit;

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
        $enableExceptions = (bool) $this->settings->get('enable-exceptions', false);
        if ($enableExceptions) {
            $this->sqlite()->enableExceptions(true);
        }
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
        return str_replace(["\0", "'"], ['', "''"], (string) $variable);
    }

    /** @return Result|false */
    public function queryResult(string $query, array $overrideTypes = [])
    {
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $rslt = @$this->sqlite()->query($query);
        if ($rslt instanceof SQLite3Result) {
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
        if ([] === $strings) {
            return $this->sqlQuote('', CommonTypes::TTEXT);
        }
        return implode(' || ', $strings);
    }

    public function sqlDatePart(string $part, string $expression): string
    {
        $format = $this->sqlDatePartFormat($part);
        return sprintf('STRFTIME(%s, %s)', $this->sqlQuote($format, self::TTEXT), $expression);
    }

    private function sqlDatePartFormat(string $part): string
    {
        switch (strtoupper($part)) {
            case 'YEAR':
                return '%Y';
            case 'MONTH':
                return '%m';
            case 'FDOM':
                return '%Y-%m-01';
            case 'FYM':
                return '%Y-%m';
            case 'FYMD':
                return '%Y-%m-%d';
            case 'DAY':
                return '%d';
            case 'HOUR':
                return '%H';
            case 'MINUTE':
                return '%M';
            case 'SECOND':
                return '%S';
            case 'FHMS':
                return '%H:%M:%S';
            default:
                throw new InvalidArgumentException("Date part $part is not valid");
        }
    }

    public function sqlIf(string $condition, string $truePart, string $falsePart): string
    {
        return 'CASE WHEN (' . $condition . ') THEN ' . $truePart . ' ELSE ' . $falsePart;
    }

    public function sqlLimit(string $query, int $requestedPage, int $recordsPerPage = 20): string
    {
        return $this->sqlLimitOffset($query, $requestedPage, $recordsPerPage);
    }

    public function sqlRandomFunc(): string
    {
        return 'random()';
    }

    private function sqlite(): SQLite3
    {
        if (null === $this->sqlite) {
            throw new RuntimeException('The current state of the connection is NULL');
        }
        return $this->sqlite;
    }
}
