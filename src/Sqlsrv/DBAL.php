<?php
namespace EngineWorks\DBAL\Sqlsrv;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL as AbstractDBAL;
use EngineWorks\DBAL\Traits\MethodSqlConcatenate;
use EngineWorks\DBAL\Traits\MethodSqlQuote;
use PDO;

/**
 * MS Sql Server implementation based on SqlSrv
 * @package EngineWorks\DBAL\Sqlsrv
 *
 * @todo: encoding: $this->settings->get('encoding')
 */
class DBAL extends AbstractDBAL
{
    use MethodSqlQuote;
    use MethodSqlConcatenate;

    /** @var PDO|null */
    protected $pdo = null;

    protected function getPDOConnectionString()
    {
        $vars = [];
        $vars['Server'] = $this->settings->get('host');
        if ($this->settings->get('port')) {
            $vars['Server'] .= ',' . $this->settings->get('port');
        }
        if ($this->settings->get('database')) {
            $vars['Database'] = $this->settings->get('database');
        }
        $return = 'sqlsrv:';
        foreach ($vars as $key => $value) {
            $return .= $key . '=' . $value . ';';
        }
        return $return;
    }

    public function connect(): bool
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
                    PDO::SQLSRV_ATTR_QUERY_TIMEOUT => max(0, (int) $this->settings->get('timeout')),
                    PDO::ATTR_FETCH_TABLE_NAMES => true,
                ]
            );
        } catch (\Throwable $ex) {
            $this->logger->info('-- Connection fail ' . $ex->getMessage());
            $this->logger->error('Cannot create PDO object for SqlSrv ' . $ex->getMessage());
            return false;
        } finally {
            error_reporting($errorLevel);
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
        $this->transactionLevel = 0;
        $this->pdo = null;
    }

    public function isConnected(): bool
    {
        return ($this->pdo instanceof PDO);
    }

    public function lastInsertedID(): int
    {
        return (int) $this->pdo()->lastInsertId();
    }

    public function sqlString($variable): string
    {
        // there are no function to escape without a link
        if ($this->isConnected()) {
            $quoted = $this->pdo()->quote($variable);
            return substr($quoted, 1, strlen($quoted) - 2);
        }
        return str_replace(["\0", "'"], ['', "''"], $variable);
    }

    /**
     * Executes a query and return an object or resource native to the driver
     * This is the internal function to do the query according to the database functions
     * It's used by queryResult and queryAffectedRows methods
     * @param string $query
     * @return \PDOStatement|false
     */
    protected function queryDriver($query)
    {
        $this->logger->debug($query);
        try {
            $stmt = $this->pdo()->prepare($query, [
                PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL,
                PDO::SQLSRV_ATTR_DIRECT_QUERY => true,
            ]);
            if (false === $stmt) {
                throw new \RuntimeException("Unable to prepare statement $query");
            }
            if (false === $stmt->execute()) {
                throw new \RuntimeException("Unable to execute statement $query");
            }
            return $stmt;
        } catch (\Throwable $ex) {
            $this->logger->info("-- Query fail with SQL: $query");
            $this->logger->error("FAIL: $query\nLast message:" . $this->getLastMessage());
            return false;
        }
    }

    public function queryResult(string $query, array $overrideTypes = [])
    {
        $stmt = $this->queryDriver($query);
        if (false !== $stmt) {
            return new Result($stmt, $overrideTypes);
        }
        return false;
    }

    protected function queryAffectedRows(string $query)
    {
        $stmt = $this->queryDriver($query);
        if (false !== $stmt) {
            return $stmt->rowCount();
        }
        return false;
    }

    protected function getLastErrorMessage(): string
    {
        $info = $this->pdo()->errorInfo();
        return '[' . $info[0] . '] ' . $info[2];
    }

    public function sqlTableEscape(string $tableName, string $asTable = ''): string
    {
        return '[' . $tableName . ']' . (('' !== $asTable) ? ' AS [' . $asTable . ']' : '');
    }

    public function sqlFieldEscape(string $tableName, string $asTable = ''): string
    {
        return '[' . $tableName . ']' . (('' !== $asTable) ? ' AS [' . $asTable . ']' : '');
    }

    public function sqlDatePart(string $part, string $expression): string
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

    public function sqlIf(string $condition, string $truePart, string $falsePart): string
    {
        return 'CASE WHEN (' . $condition . ') THEN ' . $truePart . ' ELSE ' . $falsePart . ' END';
    }

    public function sqlRandomFunc(): string
    {
        return 'RAND()';
    }

    public function sqlLimit(string $query, int $requestedPage, int $recordsPerPage = 20): string
    {
        $requestedPage = max(1, $requestedPage) - 1; // zero indexed
        $recordsPerPage = max(1, $recordsPerPage);
        $query = rtrim($query, "; \t\n\r\0\x0B")
            . ' OFFSET ' . $this->sqlQuote($recordsPerPage * $requestedPage, CommonTypes::TINT) . ' ROWS'
            . ' FETCH NEXT ' . $this->sqlQuote($recordsPerPage, CommonTypes::TINT) . ' ROWS ONLY'
            . ';';
        return $query;
    }

    public function sqlLike(
        string $fieldName,
        string $searchString,
        bool $wildcardBegin = true,
        bool $wildcardEnd = true
    ): string {
        $searchString = str_replace(
            ['[', '_', '%'],
            ['[[]' . '[_]', '[%]'],
            $searchString
        );
        return $fieldName . " LIKE '"
            . (($wildcardBegin) ? '%' : '') . $this->sqlString($searchString) . (($wildcardEnd) ? '%' : '') . "'";
    }

    protected function commandTransactionBegin()
    {
        $this->logger->debug('-- BEGIN TRANSACTION');
        $this->pdo()->beginTransaction();
    }

    protected function commandTransactionCommit()
    {
        $this->logger->debug('-- COMMIT TRANSACTION');
        $this->pdo()->commit();
    }

    protected function commandTransactionRollback()
    {
        $this->logger->debug('-- ROLLBACK TRANSACTION');
        $this->pdo()->rollBack();
    }

    protected function commandSavepoint(string $name)
    {
        $this->execute(
            'SAVE TRANSACTION ' . $this->sqlFieldEscape($name) . ';',
            "Cannot begin nested transaction $name"
        );
    }

    protected function commandReleaseSavepoint(string $name)
    {
        // do not execute, the command commit transaction does not works with save transaction
        $this->logger->debug("-- COMMIT TRANSACTION $name");
    }

    protected function commandRollbackToSavepoint(string $name)
    {
        $this->execute(
            'ROLLBACK TRANSACTION ' . $this->sqlFieldEscape($name) . ';',
            "Cannot rollback nested transaction $name"
        );
    }

    /**
     * @return PDO
     */
    private function pdo()
    {
        if (null === $this->pdo) {
            throw new \RuntimeException('The current state of the connection is NULL');
        }
        return $this->pdo;
    }
}
