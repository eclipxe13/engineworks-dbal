<?php namespace EngineWorks\DBAL\Sqlite;

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
     * @var SQLite3
     */
    protected $sqlite = null;

    /**
     * Contains the transaction level to do nested transactions
     * @var int
     */
    protected $translevel = 0;

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
            $this->sqlite->enableExceptions(true);
        }
        return true;
    }

    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->logger->info('-- Disconnection');
            $this->sqlite->close();
        }
        $this->translevel = 0;
        $this->sqlite = null;
    }

    public function isConnected()
    {
        return ($this->sqlite instanceof SQLite3);
    }

    public function lastInsertedID()
    {
        return floatval($this->sqlite->lastInsertRowID());
    }

    public function sqlString($variable)
    {
        return str_replace(["\0", "'"], ['', "''"], $variable);
        // return SQLite3::escapeString($variable);
    }

    public function queryResult($query)
    {
        if (false !== $rslt = $this->sqlite->query($query)) {
            return new Result($rslt, -1);
        }
        return false;
    }

    protected function queryAffectedRows($query)
    {
        if (false !== $this->sqlite->exec($query)) {
            return $this->sqlite->changes();
        }
        return false;
    }

    protected function getLastErrorMessage()
    {
        return (($this->isConnected())
            ? '[' . $this->sqlite->lastErrorCode() . '] ' . $this->sqlite->lastErrorMsg()
            : 'Cannot get the error because there are no active connection');
    }

    protected function sqlTableEscape($tableName, $asTable)
    {
        return '"' . $tableName . '"' . (($asTable) ? ' AS ' . '"' . $asTable . '"' : '');
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
        $format = false;
        $sql = false;
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
        }
        if ($format) {
            $sql = 'STRFTIME(' . $expression . ", '" . $format . "')";
        }
        return $sql;
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

    public function transBegin()
    {
        $this->logger->info('-- TRANSACTION BEGIN');
        $this->translevel++;
        if ($this->translevel != 1) {
            $this->logger->info("-- BEGIN (not executed because there are {$this->translevel} transactions running)");
        } else {
            $this->execute('BEGIN TRANSACTION');
        }
    }

    public function transCommit()
    {
        $this->logger->info('-- TRANSACTION COMMIT');
        $this->translevel--;
        if ($this->translevel != 0) {
            $this->logger->info("-- COMMIT (not executed because there are {$this->translevel} transactions running)");
        } else {
            $this->execute('COMMIT');
            return true;
        }
        return false;
    }

    public function transRollback()
    {
        $this->logger->info('-- TRANSACTION ROLLBACK ');
        $this->execute('ROLLBACK');
        $this->translevel--;
        if ($this->translevel != 0) {
            $this->logger->info("-- ROLLBACK (this rollback is out of sync) [{$this->translevel}]");
            return false;
        }
        return true;
    }
}
