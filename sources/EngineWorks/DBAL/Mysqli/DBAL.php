<?php namespace EngineWorks\DBAL\Mysqli;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL as AbstractDBAL;
use EngineWorks\DBAL\Traits\MethodSqlIsNull;
use EngineWorks\DBAL\Traits\MethodSqlLike;
use EngineWorks\DBAL\Traits\MethodSqlLimit;
use EngineWorks\DBAL\Traits\MethodSqlQuote;
use mysqli;

/**
 * Mysqli implementation
 * @package EngineWorks\DBAL\Mysqli
 */
class DBAL extends AbstractDBAL
{
    use MethodSqlQuote;
    use MethodSqlLike;
    use MethodSqlIsNull;
    use MethodSqlLimit;

    /**
     * Contains the connection resource for mysqli
     * @var mysqli
     */
    protected $mysqli = null;

    /**
     * Contains the transaction level to do nested transactions
     * @var int
     */
    protected $translevel = 0;

    public function connect()
    {
        // disconnect
        $this->disconnect();
        // create the mysqli object without error reporting
        $errorLevel = error_reporting(0);
        $this->mysqli = mysqli_init();
        $this->mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, $this->settings->get('connect-timeout'));
        $this->mysqli->real_connect(
            $this->settings->get('host'),
            $this->settings->get('user'),
            $this->settings->get('password'),
            $this->settings->get('database'),
            $this->settings->get('port'),
            $this->settings->get('socket'),
            $this->settings->get('flags')
        );
        error_reporting($errorLevel);
        // check for a instance of mysqli
        if (! $this->mysqli instanceof mysqli) {
            $this->logger->info('-- Connection fail');
            $this->logger->error('Cannot create mysqli object');
            return false;
        }
        // check there are no connection errors
        if ($this->mysqli->connect_errno) {
            $errormsg = "Connection fail [{$this->mysqli->connect_errno}] {$this->mysqli->connect_error}";
            $this->logger->info('-- ' . $errormsg);
            $this->logger->error($errormsg);
            $this->mysqli = null;
            return false;
        }
        // OK, we are connected
        $this->logger->info('-- Connect and database select OK');
        // set encoding if needed
        if ('' !== $encoding = $this->settings->get('encoding')) {
            $this->logger->info("-- Setting encoding to $encoding;");
            if (! $this->mysqli->set_charset($encoding)) {
                $this->logger->warning("-- Unable to set encoding to $encoding");
            }
        }
        return true;
    }

    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->logger->info('-- Disconnection');
            @$this->mysqli->close();
        }
        $this->translevel = 0;
        $this->mysqli = null;
    }

    public function isConnected()
    {
        return ($this->mysqli instanceof mysqli); // and $this->mi->ping();
    }

    public function lastInsertedID()
    {
        return floatval($this->mysqli->insert_id);
    }

    public function sqlString($variable)
    {
        if ($this->isConnected()) {
            return $this->mysqli->escape_string($variable);
        }
        // there are no function to escape without a link
        return str_replace(
            ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
            $variable
        );
    }

    /**
     * Executes a query and return an object or resource native to the driver
     * This is the internal function to do the query according to the database functions
     * It's used by queryResult and queryAffectedRows methods
     * @param string $query
     * @return mixed
     */
    protected function queryDriver($query)
    {
        $this->logger->debug($query);
        if (false === $result = $this->mysqli->query($query)) {
            $this->logger->info("-- Query fail with SQL: $query");
            $this->logger->error("FAIL: $query\nLast message:" . $this->getLastMessage());
            return false;
        }
        return $result;
    }

    public function queryResult($query)
    {
        if (false !== $result = $this->queryDriver($query)) {
            return new Result($result);
        }
        return false;
    }

    protected function queryAffectedRows($query)
    {
        if (false !== $this->queryDriver($query)) {
            return $this->mysqli->affected_rows;
        }
        return false;
    }

    protected function getLastErrorMessage()
    {
        return (($this->isConnected())
            ? '[' . $this->mysqli->errno . '] ' . $this->mysqli->error
            : 'Cannot get the error because there are no active connection');
    }

    protected function sqlTableEscape($tableName, $asTable)
    {
        return chr(96) . $tableName . chr(96) . (($asTable) ? ' AS ' . $asTable : '');
    }

    public function sqlConcatenate(...$strings)
    {
        if (! count($strings)) {
            return $this->sqlQuote('', CommonTypes::TTEXT);
        }
        return 'CONCAT(' . implode(', ', $strings) . ')';
    }

    public function sqlDatePart($part, $expression)
    {
        $format = '';
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
        $sql = '';
        if ($format) {
            $sql = 'DATE_FORMAT(' . $expression . ", '" . $format . "')";
        }
        return $sql;
    }

    public function sqlIf($condition, $truePart, $falsePart)
    {
        return 'IF(' . $condition . ', ' . $truePart . ', ' . $falsePart . ')';
    }

    public function sqlIfNull($fieldName, $nullValue)
    {
        return 'IFNULL(' . $fieldName . ', ' . $nullValue . ')';
    }

    public function sqlRandomFunc()
    {
        return 'RAND()';
    }

    public function transBegin()
    {
        $this->logger->info('-- TRANSACTION BEGIN');
        $this->translevel++;
        if ($this->translevel != 1) {
            $this->logger->info("-- BEGIN (not executed because there are {$this->translevel} transactions running)");
        } else {
            $this->execute('BEGIN');
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
            $this->logger->info('-- ROLLBACK (this rollback is out of sync) [' . $this->translevel . ']');
            return false;
        }
        return true;
    }
}
