<?php
namespace EngineWorks\DBAL\Mssql;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL as AbstractDBAL;
use EngineWorks\DBAL\Traits\MethodSqlConcatenate;
use EngineWorks\DBAL\Traits\MethodSqlIsNull;
use EngineWorks\DBAL\Traits\MethodSqlQuote;
use PDO;

/**
 * Mssql implementation
 * @package EngineWorks\DBAL\Mssql
 */
class DBAL extends AbstractDBAL
{
    use MethodSqlQuote;
    use MethodSqlIsNull;
    use MethodSqlConcatenate;

    /** @var PDO */
    protected $pdo;

    /**
     * Contains the transaction level to do nested transactions
     * @var int
     */
    protected $translevel = 0;

    protected function getPDOConnectionString()
    {
        $vars = [
            'version' => '7.0',
            'host' => $this->settings->get('host')
                . (($this->settings->get('port')) ? ':' . $this->settings->get('port') : ''),
        ];
        if ($this->settings->get('database')) {
            $vars['dbname'] = $this->settings->get('database');
        }
        if ($this->settings->get('encoding')) {
            $vars['charset'] = $this->settings->get('encoding');
        }
        $return = 'dblib:';
        foreach ($vars as $key => $value) {
            $return .= $key . '=' . $value . ';';
        }
        return $return;
    }

    public function connect()
    {
        // disconnect
        $this->disconnect();
        // create the pdo object without error reporting
        $errorLevel = error_reporting(0);
        try {
            $this->pdo = new PDO(
                $this->getPDOConnectionString(),
                $this->settings->get('user'),
                $this->settings->get('password'),
                [
                    PDO::ATTR_TIMEOUT => $this->settings->get('connect-timeout'),
                    PDO::ATTR_FETCH_TABLE_NAMES => true,
                ]
            );
        } catch (\Exception $ex) {
            $this->logger->info('-- Connection fail ' . $ex->getMessage());
            $this->logger->error('Cannot create PDO object for MS SQL ' . $ex->getMessage());
            return false;
        } finally {
            error_reporting($errorLevel);
        }
        // check for a instance of mysqli
        if (! $this->pdo instanceof PDO) {
            $this->logger->info('-- Connection fail');
            $this->logger->error('Cannot create PDO object for MS SQL');
            return false;
        }
        // check there are no connection errors

        if (false and $this->pdo->errorCode()) {
            $errormsg = $this->getLastErrorMessage();
            $this->logger->info('-- Connection fail ' . $errormsg);
            $this->logger->error($errormsg);
            $this->pdo = null;
            return false;
        }
        // OK, we are connected
        $this->logger->info('-- Connect and database select OK');
        return true;
    }

    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->logger->info('-- Disconnection');
        }
        $this->translevel = 0;
        $this->pdo = null;
    }

    public function isConnected()
    {
        return ($this->pdo instanceof PDO); // and $this->mi->ping();
    }

    public function lastInsertedID()
    {
        return floatval($this->pdo->lastInsertId());
    }

    public function sqlString($variable)
    {
        // there are no function to escape without a link
        if ('' === 'THIS IS NOT WORKING WITH MULTIBYTE STRINGS' && $this->isConnected()) {
            $quoted = $this->pdo->quote($variable);
            return substr($quoted, 1, strlen($quoted) - 2);
        }
        return str_replace(["\0", "'"], ['', "''"], $variable);
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
        try {
            if (false === $stmt = $this->pdo->query($query)) {
                throw new \RuntimeException("Unable to prepare statement $query");
            }
            return $stmt;
        } catch (\Exception $ex) {
            $this->logger->info("-- Query fail with SQL: $query");
            $this->logger->error("FAIL: $query\nLast message:" . $this->getLastMessage());
            return false;
        }
    }

    public function queryResult($query, array $overrideTypes = [])
    {
        if (false !== $stmt = $this->queryDriver($query)) {
            return new Result($stmt, -1, $overrideTypes);
        }
        return false;
    }

    protected function queryAffectedRows($query)
    {
        /* @var $stmt \PDOStatement */
        if (false !== $stmt = $this->queryDriver($query)) {
            return $stmt->rowCount();
        }
        return false;
    }

    protected function getLastErrorMessage()
    {
        if ($this->isConnected()) {
            $info = $this->pdo->errorInfo();
            return '[' . $info[0] . '] ' . $info[2];
        }
        return 'Cannot get the error because there are no active connection';
    }

    public function sqlTableEscape($tableName, $asTable = '')
    {
        return '[' . $tableName . ']' . (('' !== $asTable) ? ' AS [' . $asTable . ']' : '');
    }

    public function sqlFieldEscape($fieldName, $asFieldName = '')
    {
        return '[' . $fieldName . ']' . (('' !== $asFieldName) ? ' AS [' . $asFieldName . ']' : '');
    }

    public function sqlDatePart($part, $expression)
    {
        switch (strtoupper($part)) {
            case 'YEAR':
                return "DATEPART(yyyy, $expression)";
            case 'MONTH':
                return "DATEPART(mm, $expression)";
            case 'FDOM':
                return "CONCAT(DATEPART(yyyy, $expression), '-', DATEPART(mm, $expression), '-01')";
            case 'FYM':
                return "CONCAT(DATEPART(yyyy, $expression), '-', DATEPART(mm, $expression))";
            case 'FYMD':
                return 'CONCAT('
                    . "DATEPART(yyyy, $expression), '-', DATEPART(mm, $expression), '-', DATEPART(dd, $expression)"
                    . ')';
            case 'DAY':
                return "DATEPART(dd, $expression)";
            case 'HOUR':
                return "DATEPART(hh, $expression)";
            case 'MINUTE':
                return "DATEPART(mi, $expression)";
            case 'SECOND':
                return "DATEPART(ss, $expression)";
        }
        throw new \InvalidArgumentException("Date part $part is not valid");
    }

    public function sqlIf($condition, $truePart, $falsePart)
    {
        return 'CASE WHEN (' . $condition . ') THEN ' . $truePart . ' ELSE ' . $falsePart . ' END';
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
            $this->pdo->beginTransaction();
        }
    }

    public function transCommit()
    {
        $this->logger->info('-- TRANSACTION COMMIT');
        $this->translevel--;
        if ($this->translevel != 0) {
            $this->logger->info("-- COMMIT (not executed because there are {$this->translevel} transactions running)");
        } else {
            $this->pdo->commit();
            return true;
        }
        return false;
    }

    public function transRollback()
    {
        $this->logger->info('-- TRANSACTION ROLLBACK ');
        $this->pdo->rollBack();
        $this->translevel--;
        if ($this->translevel != 0) {
            $this->logger->info('-- ROLLBACK (this rollback is out of sync) [' . $this->translevel . ']');
            return false;
        }
        return true;
    }

    public function sqlLimit($query, $requestedPage, $recordsPerPage = 20)
    {
        $requestedPage = max(1, (int) $requestedPage) - 1; // zero indexed
        $recordsPerPage = max(1, (int) $recordsPerPage);
        $query = rtrim($query, "; \t\n\r\0\x0B")
            . ' OFFSET ' . $this->sqlQuote($recordsPerPage * $requestedPage, CommonTypes::TINT) . ' ROWS'
            . ' FETCH NEXT ' . $this->sqlQuote($recordsPerPage, CommonTypes::TINT) . ' ROWS ONLY'
            . ';';
        return $query;
    }

    public function sqlLike($fieldName, $searchString, $wildcardBegin = true, $wildcardEnd = true)
    {
        $searchString = str_replace(
            ['[', '_', '%'],
            ['[[]' . '[_]', '[%]'],
            $searchString
        );
        return $fieldName . " LIKE '"
            . (($wildcardBegin) ? '%' : '') . $this->sqlString($searchString) . (($wildcardEnd) ? '%' : '') . "'";
    }
}
