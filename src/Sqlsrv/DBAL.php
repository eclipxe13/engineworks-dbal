<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace EngineWorks\DBAL\Sqlsrv;

use EngineWorks\DBAL\Abstracts\BaseDBAL;
use EngineWorks\DBAL\Traits\MethodSqlConcatenate;
use EngineWorks\DBAL\Traits\MethodSqlLimit;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;

/**
 * MS Sql Server implementation based on SqlSrv
 * @see https://docs.microsoft.com/en-us/sql/connect/php/microsoft-php-driver-for-sql-server
 * @package EngineWorks\DBAL\Sqlsrv
 *
 * @todo: encoding: $this->settings->get('encoding')
 */
class DBAL extends BaseDBAL
{
    use MethodSqlConcatenate;
    use MethodSqlLimit;

    /** @var PDO|null */
    protected $pdo = null;

    protected function getPDOConnectionString(): string
    {
        $vars = [];
        $vars['Server'] = $this->settings->get('host');
        if ($this->settings->exists('port')) {
            $vars['Server'] .= ',' . ((int) $this->settings->get('port'));
        }
        if ($this->settings->exists('database')) {
            $vars['Database'] = $this->settings->get('database');
        }
        if ($this->settings->exists('connect-timeout')) {
            $vars['LoginTimeout'] = max(0, (int) $this->settings->get('connect-timeout', '15'));
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
                ]
            );
        } catch (Throwable $ex) {
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

    public function disconnect(): void
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
            $quoted = $this->pdo()->quote(strval($variable));
            return substr($quoted, 1, strlen($quoted) - 2);
        }
        return str_replace(["\0", "'"], ['', "''"], (string) $variable);
    }

    /**
     * Executes a query and return an object or resource native to the driver
     * This is the internal function to do the query according to the database functions
     * It's used by queryResult and queryAffectedRows methods
     *
     * @param string $query
     * @param bool $returnAffectedRows
     * @return PDOStatement|int|false
     */
    protected function queryDriver(string $query, bool $returnAffectedRows)
    {
        $this->logger->debug($query);
        try {
            if ($returnAffectedRows) {
                $affectedRows = $this->pdo()->exec($query);
                if (! is_int($affectedRows)) {
                    throw new RuntimeException("Unable to execute statement $query");
                }
                return $affectedRows;
            } else {
                $stmt = $this->pdo()->prepare($query, [
                    PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL,
                    PDO::SQLSRV_ATTR_DIRECT_QUERY => true,
                ]);
                if (! ($stmt instanceof PDOStatement)) {
                    throw new RuntimeException("Unable to prepare statement $query");
                }
                if (false === $stmt->execute()) {
                    throw new RuntimeException("Unable to execute statement $query");
                }
                return $stmt;
            }
        } catch (Throwable $ex) {
            $this->logger->info("-- Query fail with SQL: $query");
            $this->logger->error("FAIL: $query\nLast message:" . $this->getLastMessage());
            return false;
        }
    }

    public function queryResult(string $query, array $overrideTypes = [])
    {
        $stmt = $this->queryDriver($query, false);
        if ($stmt instanceof PDOStatement) {
            return new Result($stmt, $overrideTypes);
        }
        return false;
    }

    protected function queryAffectedRows(string $query)
    {
        $affectedRows = $this->queryDriver($query, true);
        if (is_int($affectedRows)) {
            return max(0, $affectedRows);
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

    public function sqlFieldEscape(string $fieldName, string $asTable = ''): string
    {
        return '[' . $fieldName . ']' . (('' !== $asTable) ? ' AS [' . $asTable . ']' : '');
    }

    public function sqlDatePart(string $part, string $expression): string
    {
        switch (strtoupper($part)) {
            case 'YEAR':
                return "DATEPART(yyyy, $expression)";
            case 'MONTH':
                return "RIGHT('0' + CAST(DATEPART(mm, $expression) AS VARCHAR(2)), 2)";
            case 'DAY':
                return "RIGHT('0' + CAST(DATEPART(dd, $expression) AS VARCHAR(2)), 2)";
            case 'HOUR':
                return "RIGHT('0' + CAST(DATEPART(hh, $expression) AS VARCHAR(2)), 2)";
            case 'MINUTE':
                return "RIGHT('0' + CAST(DATEPART(mi, $expression) AS VARCHAR(2)), 2)";
            case 'SECOND':
                return "RIGHT('0' + CAST(DATEPART(ss, $expression) AS VARCHAR(2)), 2)";
            case 'FDOM':
                return 'CONCAT'
                    . "(DATEPART(yyyy, $expression),"
                    . " '-',"
                    . " RIGHT('0' + CAST(DATEPART(mm, $expression) AS VARCHAR(2)), 2),"
                    . " '-01'"
                    . ')';
            case 'FYM':
                return 'CONCAT'
                    . "(DATEPART(yyyy, $expression),"
                    . " '-',"
                    . " RIGHT('0' + CAST(DATEPART(mm, $expression) AS VARCHAR(2)), 2)"
                    . ')';
            case 'FYMD':
                return 'CONCAT'
                    . "(DATEPART(yyyy, $expression),"
                    . " '-',"
                    . " RIGHT('0' + CAST(DATEPART(mm, $expression) AS VARCHAR(2)), 2),"
                    . " '-',"
                    . " RIGHT('0' + CAST(DATEPART(dd, $expression) AS VARCHAR(2)), 2)"
                    . ')';
            case 'FHMS':
                return 'CONCAT'
                    . "(RIGHT('0' + CAST(DATEPART(hh, $expression) AS VARCHAR(2)), 2),"
                    . " ':',"
                    . " RIGHT('0' + CAST(DATEPART(mi, $expression) AS VARCHAR(2)), 2),"
                    . " ':',"
                    . " RIGHT('0' + CAST(DATEPART(ss, $expression) AS VARCHAR(2)), 2)"
                    . ')';
        }
        throw new InvalidArgumentException("Date part $part is not valid");
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
        return $this->sqlLimitOffsetFetchNext($query, $requestedPage, $recordsPerPage);
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

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function commandTransactionBegin(): void
    {
        $this->logger->debug('-- PDO TRANSACTION BEGIN'); // send message because it didn't run execute command
        $this->pdo()->beginTransaction();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function commandTransactionCommit(): void
    {
        $this->logger->debug('-- PDO TRANSACTION COMMIT'); // send message because it didn't run execute command
        $this->pdo()->commit();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function commandTransactionRollback(): void
    {
        $this->logger->debug('-- PDO TRANSACTION ROLLBACK'); // send message because it didn't run execute command
        $this->pdo()->rollBack();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function commandSavepoint(string $name): void
    {
        $this->execute(
            'SAVE TRANSACTION ' . $this->sqlFieldEscape($name) . ';',
            "Cannot begin nested transaction $name"
        );
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function commandReleaseSavepoint(string $name): void
    {
        // do not execute, the command commit transaction does not works with save transaction
        $this->logger->debug("-- PDO TRANSACTION COMMIT $name"); // send message because it didn't run execute command
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function commandRollbackToSavepoint(string $name): void
    {
        $this->execute(
            'ROLLBACK TRANSACTION ' . $this->sqlFieldEscape($name) . ';',
            "Cannot rollback nested transaction $name"
        );
    }

    private function pdo(): PDO
    {
        if (null === $this->pdo) {
            throw new RuntimeException('The current state of the connection is NULL');
        }
        return $this->pdo;
    }
}
