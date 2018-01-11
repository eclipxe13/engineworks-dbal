<?php
namespace EngineWorks\DBAL\Sqlite;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL as AbstractDBAL;
use EngineWorks\DBAL\Traits\MethodSqlIsNull;
use EngineWorks\DBAL\Traits\MethodSqlLike;
use EngineWorks\DBAL\Traits\MethodSqlLimit;
use EngineWorks\DBAL\Traits\MethodSqlQuote;
use SQLite3;

class DBAL extends AbstractDBAL
{
    use MethodSqlQuote;
    use MethodSqlLike;
    use MethodSqlIsNull;
    use MethodSqlLimit;

    /**
     * Contains the connection resource for SQLite3
     * @var SQLite3|null
     */
    protected $sqlite = null;

    public function connect()
    {
        // disconnect, this will reset object properties
        $this->disconnect();
        // create the sqlite3 object without error reporting
        $level = error_reporting(0);
        try {
            $this->sqlite = new SQLite3($this->settings->get('filename'), $this->settings->get('flags'));
        } catch (\Exception $ex) {
            $this->logger->info('-- Connection fail');
            $this->logger->error('Cannot create SQLite3 object: ' . $ex->getMessage());
            return false;
        } finally {
            error_reporting($level);
        }
        // OK, we are connected
        $this->logger->info('-- Connection success');
        if ($this->settings->get('enable-exceptions', false)) {
            $this->sqlite()->enableExceptions(true);
        }
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

    public function isConnected()
    {
        return ($this->sqlite instanceof SQLite3);
    }

    public function lastInsertedID()
    {
        return floatval($this->sqlite()->lastInsertRowID());
    }

    public function sqlString($variable)
    {
        return str_replace(["\0", "'"], ['', "''"], $variable);
    }

    public function queryResult($query, array $overrideTypes = [])
    {
        if (false !== $rslt = $this->sqlite()->query($query)) {
            return new Result($rslt, -1, $overrideTypes);
        }
        return false;
    }

    protected function queryAffectedRows($query)
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
            return $this->sqlite()->changes();
        }
        return false;
    }

    protected function getLastErrorMessage()
    {
        if ($this->isConnected()) {
            return '[' . $this->sqlite()->lastErrorCode() . '] ' . $this->sqlite()->lastErrorMsg();
        }
        return 'Cannot get the error because there are no active connection';
    }

    public function sqlTableEscape($tableName, $asTable = '')
    {
        return '"' . $tableName . '"' . (('' !== $asTable) ? ' AS ' . '"' . $asTable . '"' : '');
    }

    public function sqlFieldEscape($fieldName, $asFieldName = '')
    {
        return '"' . $fieldName . '"' . (('' !== $asFieldName) ? ' AS ' . '"' . $asFieldName . '"' : '');
    }

    public function sqlConcatenate(...$strings)
    {
        if (! count($strings)) {
            return $this->sqlQuote('', CommonTypes::TTEXT);
        }
        return implode(' || ', $strings);
    }

    public function sqlDatePart($part, $expression)
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

    public function sqlIf($condition, $truePart, $falsePart)
    {
        return 'CASE WHEN (' . $condition . ') THEN ' . $truePart . ' ELSE ' . $falsePart;
    }

    public function sqlIfNull($fieldName, $nullValue)
    {
        return 'IFNULL(' . $fieldName . ', ' . $nullValue . ')';
    }

    public function sqlRandomFunc()
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
