<?php
namespace EngineWorks\DBAL\Sqlite;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL as AbstractDBAL;
use EngineWorks\DBAL\Traits\MethodSqlLike;
use EngineWorks\DBAL\Traits\MethodSqlLimit;
use EngineWorks\DBAL\Traits\MethodSqlQuote;
use SQLite3;

class DBAL extends AbstractDBAL
{
    use MethodSqlQuote;
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
            $this->sqlite = new SQLite3(
                (string) $this->settings->get('filename', ':memory:'),
                ($this->settings->exists('flags')) ? (int) $this->settings->get('flags') : null
            );
        } catch (\Throwable $ex) {
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

    public function disconnect()
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
        return (int) $this->sqlite()->lastInsertRowID();
    }

    public function sqlString($variable): string
    {
        return str_replace(["\0", "'"], ['', "''"], $variable);
    }

    public function queryResult(string $query, array $overrideTypes = [])
    {
        if (false !== $rslt = @$this->sqlite()->query($query)) {
            return new Result($rslt, $overrideTypes);
        }
        return false;
    }

    protected function queryAffectedRows(string $query)
    {
        $this->logger->debug($query);
        try {
            $exec = $this->sqlite()->exec($query);
        } catch (\Exception $ex) {
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

    public function sqlFieldEscape(string $tableName, string $asTable = ''): string
    {
        return '"' . $tableName . '"' . (('' !== $asTable) ? ' AS ' . '"' . $asTable . '"' : '');
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
        switch (strtoupper($part)) {
            case 'YEAR':
                $format = '%Y';
                break;
            case 'MONTH':
                $format = '%m';
                break;
            case 'FDOM':
                $format = '%Y-%m-01';
                break;
            case 'FYM':
                $format = '%Y-%m';
                break;
            case 'FYMD':
                $format = '%Y-%m-%d';
                break;
            case 'DAY':
                $format = '%d';
                break;
            case 'HOUR':
                $format = '%H';
                break;
            case 'MINUTE':
                $format = '%i';
                break;
            case 'SECOND':
                $format = '%s';
                break;
            default:
                throw new \InvalidArgumentException("Date part $part is not valid");
        }
        return 'STRFTIME(' . $expression . ", '" . $format . "')";
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
            throw new \RuntimeException('The current state of the connection is NULL');
        }
        return $this->sqlite;
    }
}
