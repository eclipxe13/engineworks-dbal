<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace EngineWorks\DBAL\Mysqli;

use EngineWorks\DBAL\DBAL as AbstractDBAL;
use EngineWorks\DBAL\Traits\MethodSqlConcatenate;
use EngineWorks\DBAL\Traits\MethodSqlDatePartFormatAnsi;
use EngineWorks\DBAL\Traits\MethodSqlLike;
use EngineWorks\DBAL\Traits\MethodSqlLimit;
use EngineWorks\DBAL\Traits\MethodSqlQuote;
use mysqli;
use mysqli_result;
use RuntimeException;

/**
 * Mysqli implementation
 * @package EngineWorks\DBAL\Mysqli
 */
class DBAL extends AbstractDBAL
{
    use MethodSqlQuote;
    use MethodSqlLike;
    use MethodSqlLimit;
    use MethodSqlConcatenate;
    use MethodSqlDatePartFormatAnsi;

    /**
     * Contains the connection resource for mysqli
     * @var mysqli|null
     */
    protected $mysqli = null;

    public function connect(): bool
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
            intval($this->settings->get('port')),
            strval($this->settings->get('socket')),
            intval($this->settings->get('flags'))
        );
        error_reporting($errorLevel);
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
        if ('' !== $encoding = $this->settings->get('encoding', '')) {
            $this->logger->info("-- Setting encoding to $encoding;");
            if (! $this->mysqli->set_charset($encoding)) {
                $this->logger->warning("-- Unable to set encoding to $encoding");
            }
        }
        return true;
    }

    public function disconnect(): void
    {
        if ($this->isConnected()) {
            $this->logger->info('-- Disconnection');
            $this->mysqli()->close();
        }
        $this->transactionLevel = 0;
        $this->mysqli = null;
    }

    public function isConnected(): bool
    {
        return ($this->mysqli instanceof mysqli);
    }

    public function lastInsertedID(): int
    {
        return (int) $this->mysqli()->insert_id;
    }

    public function sqlString($variable): string
    {
        if ($this->isConnected()) {
            return $this->mysqli()->escape_string(strval($variable));
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
     * @return mysqli_result|bool
     */
    protected function queryDriver($query)
    {
        $this->logger->debug($query);
        $result = $this->mysqli()->query($query);
        if (false === $result) {
            $this->logger->info("-- Query fail with SQL: $query");
            $this->logger->error("FAIL: $query\nLast message:" . $this->getLastMessage());
        }
        return $result;
    }

    public function queryResult(string $query, array $overrideTypes = [])
    {
        $result = $this->queryDriver($query);
        if ($result instanceof mysqli_result) {
            return new Result($result, $overrideTypes);
        }
        if (true === $result) {
            $this->logger->warning("-- The query $query was executed but it does not return a result");
        }
        return false;
    }

    protected function queryAffectedRows(string $query)
    {
        if (false !== $this->queryDriver($query)) {
            return max(0, $this->mysqli()->affected_rows);
        }
        return false;
    }

    protected function getLastErrorMessage(): string
    {
        return '[' . $this->mysqli()->errno . '] ' . $this->mysqli()->error;
    }

    public function sqlTableEscape(string $tableName, string $asTable = ''): string
    {
        return '`' . $tableName . '`' . (('' !== $asTable) ? ' AS `' . $asTable . '`' : '');
    }

    public function sqlFieldEscape(string $fieldName, string $asTable = ''): string
    {
        return '`' . $fieldName . '`' . (('' !== $asTable) ? ' AS `' . $asTable . '`' : '');
    }

    public function sqlDatePart(string $part, string $expression): string
    {
        $format = $this->sqlDatePartFormatAnsi($part);
        return sprintf('DATE_FORMAT(%s, %s)', $expression, $this->sqlQuote($format, self::TTEXT));
    }

    public function sqlIf(string $condition, string $truePart, string $falsePart): string
    {
        return 'IF(' . $condition . ', ' . $truePart . ', ' . $falsePart . ')';
    }

    public function sqlRandomFunc(): string
    {
        return 'RAND()';
    }

    protected function commandTransactionBegin(): void
    {
        $this->execute('START TRANSACTION', 'Cannot start transaction');
    }

    /**
     * @return mysqli
     */
    private function mysqli()
    {
        if (null === $this->mysqli) {
            throw new RuntimeException('The current state of the connection is NULL');
        }
        return $this->mysqli;
    }
}
