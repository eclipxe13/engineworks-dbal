<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace EngineWorks\DBAL\Mysqli;

use EngineWorks\DBAL\Abstracts\BaseDBAL;
use EngineWorks\DBAL\Traits\MethodSqlConcatenate;
use EngineWorks\DBAL\Traits\MethodSqlLike;
use EngineWorks\DBAL\Traits\MethodSqlLimit;
use InvalidArgumentException;
use LogicException;
use mysqli;
use mysqli_driver;
use mysqli_result;
use RuntimeException;

/**
 * Mysqli implementation
 */
class DBAL extends BaseDBAL
{
    use MethodSqlLike;
    use MethodSqlLimit;
    use MethodSqlConcatenate;

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
        $mysqli = mysqli_init();
        if (! $mysqli instanceof mysqli) {
            error_reporting($errorLevel);
            throw new LogicException('Unable to create Mysqli empty object');
        }
        if (MYSQLI_REPORT_OFF !== (new mysqli_driver())->report_mode) {
            throw new RuntimeException('Mysqli error report mode should be MYSQLI_REPORT_OFF');
        }
        $this->mysqli = $mysqli;
        $connectTimeout = $this->settings->get('connect-timeout');
        if (null !== $connectTimeout) {
            $this->mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, (int) $connectTimeout);
        }
        $this->mysqli->real_connect(
            (string) $this->settings->get('host'),
            (string) $this->settings->get('user'),
            (string) $this->settings->get('password'),
            (string) $this->settings->get('database'),
            (int) $this->settings->get('port'),
            (string) $this->settings->get('socket'),
            (int) $this->settings->get('flags')
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
        $encoding = (string) $this->settings->get('encoding', '');
        if ('' !== $encoding) {
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
        return $this->mysqli instanceof mysqli;
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
            (string) $variable
        );
    }

    /**
     * Executes a query and return an object or resource native to the driver
     * This is the internal function to do the query according to the database functions
     * It's used by queryResult and queryAffectedRows methods
     * @param string $query
     * @return mysqli_result<mixed>|bool
     */
    protected function queryDriver(string $query)
    {
        $this->logger->debug($query);
        $result = $this->mysqli()->query($query);
        if (false === $result) {
            $this->logger->info("-- Query fail with SQL: $query");
            $this->logger->error("FAIL: $query\nLast message:" . $this->getLastMessage());
        }
        return $result;
    }

    /** @return Result|false */
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
            return max(0, (int) $this->mysqli()->affected_rows);
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
        $format = $this->sqlDatePartFormat($part);
        return sprintf('DATE_FORMAT(%s, %s)', $expression, $this->sqlQuote($format, self::TTEXT));
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
                return '%i';
            case 'SECOND':
                return '%s';
            case 'FHMS':
                return '%H:%i:%s';
            default:
                throw new InvalidArgumentException("Date part $part is not valid");
        }
    }

    public function sqlIf(string $condition, string $truePart, string $falsePart): string
    {
        return 'IF(' . $condition . ', ' . $truePart . ', ' . $falsePart . ')';
    }

    public function sqlLimit(string $query, int $requestedPage, int $recordsPerPage = 20): string
    {
        return $this->sqlLimitOffset($query, $requestedPage, $recordsPerPage);
    }

    public function sqlRandomFunc(): string
    {
        return 'RAND()';
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function commandTransactionBegin(): void
    {
        $this->execute('START TRANSACTION', 'Cannot start transaction');
    }

    private function mysqli(): mysqli
    {
        if (null === $this->mysqli) {
            throw new RuntimeException('The current state of the connection is NULL');
        }
        return $this->mysqli;
    }
}
