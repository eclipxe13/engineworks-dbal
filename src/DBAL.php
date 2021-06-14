<?php

declare(strict_types=1);

namespace EngineWorks\DBAL;

use EngineWorks\DBAL\Exceptions\QueryException;
use EngineWorks\DBAL\Internal\NumericParser;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;

/**
 * Database Abstraction Layer Abstract Class
 */
abstract class DBAL implements CommonTypes, LoggerAwareInterface
{
    /** @var LoggerInterface */
    protected $logger;

    use LoggerAwareTrait;

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Settings object
     * @var Settings
     */
    protected $settings;

    /**
     * Contains the transaction level to do nested transactions
     * @var int
     */
    protected $transactionLevel = 0;

    /**
     * Contains the prevent commit state of transactions
     * @var bool
     */
    protected $preventCommit = false;

    /**
     * @param Settings $settings
     * @param LoggerInterface|null $logger If null then a NullLogger will be used
     */
    public function __construct(Settings $settings, LoggerInterface $logger = null)
    {
        $this->settings = $settings;
        $this->setLogger($logger ?? new NullLogger());
    }

    /**
     * Destructor - force to call disconnect
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Try to connect to the database with the current configured options
     * If connected it will disconnect first
     *
     * @return bool true if the connection was made
     */
    abstract public function connect(): bool;

    /**
     * Disconnect
     */
    abstract public function disconnect(): void;

    /**
     * Return the state of the connection
     * @return bool
     */
    abstract public function isConnected(): bool;

    /**
     * Get the last inserted id, use it after an insert
     * @return int
     */
    abstract public function lastInsertedID(): int;

    /**
     * Implement the transaction begin command
     */
    protected function commandTransactionBegin(): void
    {
        $this->execute('BEGIN TRANSACTION', 'Cannot start transaction');
    }

    /**
     * Implement the transaction commit command
     */
    protected function commandTransactionCommit(): void
    {
        $this->execute('COMMIT', 'Cannot commit transaction');
    }

    /**
     * Implement the transaction rollback command
     */
    protected function commandTransactionRollback(): void
    {
        $this->execute('ROLLBACK', 'Cannot rollback transaction');
    }

    /**
     * Implement the savepoint command
     * @param string $name
     */
    protected function commandSavepoint(string $name): void
    {
        $this->execute("SAVEPOINT $name", "Cannot create savepoint $name");
    }

    /**
     * Implement the release savepoint command
     * @param string $name
     */
    protected function commandReleaseSavepoint(string $name): void
    {
        $this->execute("RELEASE SAVEPOINT $name", "Cannot release savepoint $name");
    }

    /**
     * Implement the rollback to savepoint command
     * @param string $name
     */
    protected function commandRollbackToSavepoint(string $name): void
    {
        $this->execute("ROLLBACK TO SAVEPOINT $name", "Cannot rollback to savepoint $name");
    }

    /**
     * Return the current transaction level (managed by the object, not the database)
     * @return int
     */
    final public function getTransactionLevel(): int
    {
        return $this->transactionLevel;
    }

    /**
     * Start a transaction
     */
    final public function transBegin(): void
    {
        $this->logger->info('-- TRANSACTION BEGIN');
        if (0 === $this->transactionLevel) {
            $this->commandTransactionBegin();
        } else {
            $this->commandSavepoint("LEVEL_{$this->transactionLevel}");
        }
        $this->transactionLevel = $this->transactionLevel + 1;
    }

    /**
     * Commit a transaction
     */
    final public function transCommit(): void
    {
        $this->logger->info('-- TRANSACTION COMMIT');
        // reduce the transaction level
        if (0 === $this->transactionLevel) {
            trigger_error('Try to call commit without a transaction', E_USER_NOTICE);
            return;
        }
        $this->transactionLevel = $this->transactionLevel - 1;
        // do commit or savepoint
        if (0 === $this->transactionLevel) {
            if ($this->transPreventCommit()) {
                $this->transactionLevel = 1;
                trigger_error('Try to call final commit with prevent commit enabled', E_USER_ERROR);
            }
            $this->commandTransactionCommit();
        } else {
            $this->commandReleaseSavepoint("LEVEL_{$this->transactionLevel}");
        }
    }

    /**
     * Rollback a transaction
     */
    final public function transRollback(): void
    {
        $this->logger->info('-- TRANSACTION ROLLBACK ');
        // reduce the transaction level
        if (0 === $this->transactionLevel) {
            trigger_error('Try to call rollback without a transaction', E_USER_NOTICE);
            return;
        }
        $this->transactionLevel = $this->transactionLevel - 1;
        // do rollback or savepoint
        if (0 === $this->transactionLevel) {
            $this->commandTransactionRollback();
        } else {
            $this->commandRollbackToSavepoint("LEVEL_{$this->transactionLevel}");
        }
    }

    /**
     * This function prevent the upper transaction to commit
     * In case of commit the transCommitMehod will trigger an error and return without commit
     *
     * If argument $preventCommit is null then this function will return the current prevent commit state
     * Otherwise, It will set the prevent commit state to the argument value and return the previous value
     *
     * @param bool|null $preventCommit
     * @return bool
     */
    final public function transPreventCommit(bool $preventCommit = null): bool
    {
        if (null === $preventCommit) {
            return $this->preventCommit;
        }
        $previous = $this->preventCommit;
        $this->preventCommit = $preventCommit;
        return $previous;
    }

    /**
     * Escapes a table name including its prefix and optionally renames it.
     * This is the same method as sqlTableEscape but using the table prefix from settings.
     * Optionaly renames it as an alias.
     *
     * @param string $tableName
     * @param string $asTable
     * @return string
     */
    final public function sqlTable(string $tableName, string $asTable = ''): string
    {
        return $this->sqlTableEscape($this->settings->get('prefix', '') . $tableName, $asTable);
    }

    /**
     * Escapes a table name to not get confused with reserved words or invalid chars.
     * Optionaly renames it as an alias.
     *
     * @param string $tableName
     * @param string $asTable
     * @return string
     */
    abstract public function sqlTableEscape(string $tableName, string $asTable = ''): string;

    /**
     * Return a field name
     * Optionaly renames it as an alias.
     * This function do not escape the field name.
     *
     * Use example: $dbal->sqlField('COUNT(*)', 'rows'); or $dbal->sqlField('name')
     *
     * @see self::sqlFieldEscape
     * @param string $fieldName
     * @param string $asFieldName
     * @return string
     */
    final public function sqlField(string $fieldName, string $asFieldName = ''): string
    {
        return $fieldName . (('' !== $asFieldName) ? ' AS ' . $this->sqlFieldEscape($asFieldName) : '');
    }

    /**
     * Escapes a table name to not get confused with reserved words or invalid chars.
     * Optionaly renames it as an alias.
     *
     * @param string $fieldName
     * @param string $asTable
     * @return string
     */
    abstract public function sqlFieldEscape(string $fieldName, string $asTable = ''): string;

    /**
     * Parses a value to secure SQL
     *
     * @param mixed $variable
     * @param string $commonType
     * @param bool $includeNull
     * @return string
     */
    final public function sqlQuote(
        $variable,
        string $commonType = CommonTypes::TTEXT,
        bool $includeNull = false
    ): string {
        if ($includeNull && null === $variable) {
            return 'NULL';
        }
        // CommonTypes::TTEXT is here because is the most common used type
        if (CommonTypes::TTEXT === $commonType) {
            return "'" . $this->sqlString($variable) . "'";
        }
        if (CommonTypes::TINT === $commonType) {
            return $this->sqlQuoteParseNumber($variable, true);
        }
        if (CommonTypes::TNUMBER === $commonType) {
            return $this->sqlQuoteParseNumber($variable, false);
        }
        if (CommonTypes::TBOOL === $commonType) {
            return ($variable) ? '1' : '0';
        }
        if (CommonTypes::TDATE === $commonType) {
            return "'" . date('Y-m-d', (int) $variable) . "'";
        }
        if (CommonTypes::TTIME === $commonType) {
            return "'" . date('H:i:s', (int) $variable) . "'";
        }
        if (CommonTypes::TDATETIME === $commonType) {
            return "'" . date('Y-m-d H:i:s', (int) $variable) . "'";
        }
        return "'" . $this->sqlString($variable) . "'";
    }

    /**
     * @param mixed $value
     * @param bool $asInteger
     * @return string
     */
    private function sqlQuoteParseNumber($value, bool $asInteger): string
    {
        return (new NumericParser())->parseAsEnglish((string) $value, $asInteger);
    }

    /**
     * Parses values to secure SQL for IN operator
     *
     * @param mixed[] $values
     * @param string $commonType
     * @param bool $includeNull
     * @return string example "(1, 3, 5)"
     * @throws RuntimeException if the array of values is empty
     */
    final public function sqlQuoteIn(
        array $values,
        string $commonType = CommonTypes::TTEXT,
        bool $includeNull = false
    ): string {
        if (0 === count($values)) {
            throw new RuntimeException('The array of values passed to DBAL::sqlQuoteIn is empty');
        }
        return ''
            . '('
            . implode(', ', array_map(function ($value) use ($commonType, $includeNull) {
                return $this->sqlQuote($value, $commonType, $includeNull);
            }, array_unique($values)))
            . ')';
    }

    /**
     * Return a comparison condition using IN operator
     * If the array of values is empty then create an always false condition "0 = 1"
     *
     * @see sqlQuoteIn
     *
     * @param string $field
     * @param mixed[] $values
     * @param string $commonType
     * @param bool $positive Set to FALSE to perform a NOT IN comparison
     * @param bool $includeNull
     *
     * @return string
     */
    final public function sqlIn(
        string $field,
        array $values,
        string $commonType = CommonTypes::TTEXT,
        bool $positive = true,
        bool $includeNull = false
    ): string {
        if (! $positive) {
            trigger_error(
                __METHOD__ . ' with argument $positive = false is deprecated, use DBAL::sqlNotIn',
                E_USER_NOTICE
            );
            return $this->sqlNotIn($field, $values, $commonType, $includeNull);
        }
        if (0 === count($values)) {
            return '0 = 1';
        }
        return $field . ' IN ' . $this->sqlQuoteIn($values, $commonType, $includeNull);
    }

    /**
     * Return a NEGATIVE comparison condition using IN operator
     * If the array of values is empty then create an always true condition "1 = 1"
     *
     * @see sqlQuoteIn
     *
     * @param string $field
     * @param mixed[] $values
     * @param string $commonType
     * @param bool $includeNull
     *
     * @return string
     */
    final public function sqlNotIn(
        string $field,
        array $values,
        string $commonType = CommonTypes::TTEXT,
        bool $includeNull = false
    ): string {
        if (0 === count($values)) {
            return '1 = 1';
        }
        return $field . ' NOT IN ' . $this->sqlQuoteIn($values, $commonType, $includeNull);
    }

    /**
     * Quote as string
     *
     * @param scalar $variable
     * @return string
     */
    abstract public function sqlString($variable): string;

    /**
     * Random function
     * @return string
     */
    abstract public function sqlRandomFunc(): string;

    /**
     * Return a comparison condition against null
     *
     * @param string $field
     * @param bool $positive Set to FALSE to perform a IS NOT NULL comparison
     * @return string
     */
    final public function sqlIsNull(string $field, bool $positive = true): string
    {
        if (! $positive) {
            trigger_error(
                __METHOD__ . ' with argument $positive = false is deprecated, use DBAL::sqlIsNotNull',
                E_USER_NOTICE
            );
            return $this->sqlIsNotNull($field);
        }
        return $field . ' IS NULL';
    }

    /**
     * Return a NEGATIVE comparison condition against null
     *
     * @param string $field
     * @return string
     */
    final public function sqlIsNotNull(string $field): string
    {
        return $field . ' IS NOT NULL';
    }

    /**
     * Return a condition using between operator quoting lower and upper bounds
     *
     * @param string $field
     * @param mixed $lowerBound
     * @param mixed $upperBound
     * @param string $commonType
     * @return string
     */
    final public function sqlBetweenQuote(
        string $field,
        $lowerBound,
        $upperBound,
        string $commonType = CommonTypes::TTEXT
    ): string {
        return $field
            . ' BETWEEN ' . $this->sqlQuote($lowerBound, $commonType)
            . ' AND ' . $this->sqlQuote($upperBound, $commonType)
            ;
    }

    /**
     * If function
     * @param string $condition
     * @param string $truePart
     * @param string $falsePart
     * @return string
     */
    abstract public function sqlIf(string $condition, string $truePart, string $falsePart): string;

    /**
     * If null function
     *
     * @param string $fieldName
     * @param string $nullValue
     * @return string
     */
    final public function sqlIfNull(string $fieldName, string $nullValue): string
    {
        return 'IFNULL(' . $fieldName . ', ' . $nullValue . ')';
    }

    /**
     * Transform a SELECT query to be paged
     * This function add a semicolon at the end of the sentence
     *
     * @param string $query
     * @param int $requestedPage
     * @param int $recordsPerPage
     * @return string
     */
    abstract public function sqlLimit(string $query, int $requestedPage, int $recordsPerPage = 20): string;

    /**
     * Like operator (simple)
     *
     * @param string $fieldName
     * @param string $searchString
     * @param bool $wildcardBegin
     * @param bool $wildcardEnd
     * @return string
     */
    abstract public function sqlLike(
        string $fieldName,
        string $searchString,
        bool $wildcardBegin = true,
        bool $wildcardEnd = true
    ): string;

    /**
     * Like operator (advanced)
     *
     * @param string $fieldName
     * @param string $searchTerms
     * @param bool $matchAnyTerm
     * @param string $termsSeparator
     * @return string
     */
    final public function sqlLikeSearch(
        string $fieldName,
        string $searchTerms,
        bool $matchAnyTerm = true,
        string $termsSeparator = ' '
    ): string {
        return implode(
            ($matchAnyTerm) ? ' OR ' : ' AND ',
            array_map(function (string $term) use ($fieldName): string {
                return '(' . $this->sqlLike($fieldName, $term) . ')';
            }, array_unique(array_filter(explode($termsSeparator, $searchTerms) ?: [])))
        );
    }

    /**
     * Concatenation, as this can allow fields and strings, all strings must be previously escaped
     *
     * @param string ...$strings fields and escaped strings
     * @return string
     */
    abstract public function sqlConcatenate(...$strings): string;

    /**
     * Function to get a part of a date using sql formatting functions
     * Valid part are: YEAR, MONTH, FDOM (First Day Of Month), FYM (Format Year Month),
     * FYMD (Format Year Month Date), DAY, HOUR. MINUTE, SECOND
     * @param string $part
     * @param string $expression
     * @return string
     */
    abstract public function sqlDatePart(string $part, string $expression): string;

    /* -----
     * protected methods (to override)
     */

    /**
     * Executes a query and return a Result
     *
     * @param string $query
     * @param array<string, string> $overrideTypes use this to override detected types
     * @return Result|false
     */
    abstract public function queryResult(string $query, array $overrideTypes = []);

    /**
     * Executes a query and return the number of affected rows
     *
     * @param string $query
     * @return int|false FALSE if the query fails
     */
    abstract protected function queryAffectedRows(string $query);

    /**
     * Return the last error message from the driver
     * @return string
     */
    abstract protected function getLastErrorMessage(): string;

    /**
     * Executes a query and return the affected rows
     *
     * @param string $query
     * @param string $exceptionMessage Throws QueryException with message on error
     * @return int|false Number of affected rows or FALSE on error
     * @throws RuntimeException if the result is FALSE and the $exceptionMessage was set
     */
    final public function execute(string $query, string $exceptionMessage = '')
    {
        $return = $this->queryAffectedRows($query);
        if (false === $return) {
            if ('' !== $exceptionMessage) {
                $previous = $this->getLastErrorMessage() ? new RuntimeException($this->getLastErrorMessage()) : null;
                throw new QueryException($exceptionMessage, $query, 0, $previous);
            }
            return false;
        }

        $this->logger->info("-- AffectedRows: $return");
        return $return;
    }

    /**
     * Executes a query and return a Result
     *
     * @deprecated since version 1.5.0 in favor of queryResult
     * @see queryResult
     * @access private
     * @param string $query
     * @return Result|false
     */
    final public function query(string $query)
    {
        trigger_error(__METHOD__ . ' is deprecated, use queryResult instead', E_USER_DEPRECATED);
        return $this->queryResult($query);
    }

    /**
     * Get the first field in row of a query
     *
     * @param string $query
     * @param mixed $default
     * @return mixed
     */
    final public function queryOne(string $query, $default = false)
    {
        return current($this->queryRow($query) ?: [$default]);
    }

    /**
     * Get the first row of a query
     *
     * @param string $query
     * @return array<string, mixed>|false
     */
    final public function queryRow(string $query)
    {
        $result = $this->queryResult($query);
        if (false === $result) {
            return false;
        }

        $row = $result->fetchRow();
        if (false === $row) {
            return false;
        }

        return $row;
    }

    /**
     * Get the first row of a query, the values are in common types
     *
     * @param string $query
     * @param array<string, string> $overrideTypes
     * @return array<string, mixed>|false
     */
    final public function queryValues(string $query, array $overrideTypes = [])
    {
        $recordset = $this->queryRecordset($query, '', [], $overrideTypes);
        if (false === $recordset) {
            return false;
        }
        if ($recordset->eof()) {
            return false;
        }
        return $recordset->values;
    }

    /**
     * Get an array of rows of a query
     *
     * @param string $query
     * @return array<int, array<string, mixed>>|false
     */
    final public function queryArray(string $query)
    {
        $result = $this->queryResult($query);
        if (false === $result) {
            return false;
        }

        $return = [];
        while (false !== $row = $result->fetchRow()) {
            $return[] = $row;
        }
        return $return;
    }

    /**
     * Get an array of rows of a query, the values are in common types
     *
     * @param string $query
     * @param array<string, string> $overrideTypes
     * @return array<int, array<string, mixed>>|false
     */
    final public function queryArrayValues(string $query, array $overrideTypes = [])
    {
        $recordset = $this->queryRecordset($query, '', [], $overrideTypes);
        if (false === $recordset) {
            return false;
        }

        $return = [];
        while (! $recordset->eof()) {
            $return[] = $recordset->values;
            $recordset->moveNext();
        }
        return $return;
    }

    /**
     * Get an array of rows of a query
     * It uses the keyField as index of the array
     *
     * @param string $query
     * @param string $keyField
     * @param string $keyPrefix
     * @return array<array<string, mixed>>|false
     */
    final public function queryArrayKey(string $query, string $keyField, string $keyPrefix = '')
    {
        $result = $this->queryResult($query);
        if (false === $result) {
            return false;
        }

        $return = [];
        /** @noinspection PhpAssignmentInConditionInspection */
        while ($row = $result->fetchRow()) {
            if (! array_key_exists($keyField, $row)) {
                return false;
            }
            $return[$keyPrefix . $row[$keyField]] = $row;
        }
        return $return;
    }

    /**
     * Return a one dimension array with keys and values defined by keyField and valueField
     * The resulting array keys can have a prefix defined by keyPrefix
     * If two keys collapse then the last value will be used
     * Always return an array, even if it fails
     *
     * @param string $query
     * @param string $keyField
     * @param string $valueField
     * @param string $keyPrefix
     * @param mixed $default
     * @return mixed[]
     */
    final public function queryPairs(
        string $query,
        string $keyField,
        string $valueField,
        string $keyPrefix = '',
        $default = false
    ): array {
        $array = $this->queryArray($query) ?: [];
        $return = [];
        foreach ($array as $row) {
            $return[$keyPrefix . ($row[$keyField] ?? '')] = $row[$valueField] ?? $default;
        }
        return $return;
    }

    /**
     * Return one dimensional array with the values of one column of the query
     * If the field is not set then the function will take the first column
     *
     * @param string $query
     * @param string $field
     * @return array<int, mixed>|false
     */
    final public function queryArrayOne(string $query, string $field = '')
    {
        $result = $this->queryResult($query);
        if (false === $result) {
            return false;
        }

        $return = [];
        $verifiedFieldName = false;
        /** @noinspection PhpAssignmentInConditionInspection */
        while ($row = $result->fetchRow()) {
            if ('' === $field) {
                $keys = array_keys($row);
                $field = $keys[0];
                $verifiedFieldName = true;
            }
            if (! $verifiedFieldName) {
                if (! array_key_exists($field, $row)) {
                    return false;
                }
                $verifiedFieldName = true;
            }
            $return[] = $row[$field] ?? null;
        }
        return $return;
    }

    /**
     * Return the result of the query as a imploded string with all the values of the first column
     *
     * @param string $query
     * @param string $default
     * @param string $separator
     * @return string
     */
    final public function queryOnString(string $query, string $default = '', string $separator = ', '): string
    {
        $array = $this->queryArrayOne($query);
        if (false === $array) {
            return $default;
        }
        return implode($separator, $array);
    }

    /**
     * Returns a Recordset Object from the query, false if error
     *
     * @param string $query
     * @param string $overrideEntity
     * @param string[] $overrideKeys
     * @param array<string, string> $overrideTypes
     * @return Recordset|false
     */
    final public function queryRecordset(
        string $query,
        string $overrideEntity = '',
        array $overrideKeys = [],
        array $overrideTypes = []
    ) {
        try {
            $recordset = $this->createRecordset($query, $overrideEntity, $overrideKeys, $overrideTypes);
        } catch (Throwable $exception) {
            $this->logger->error("DBAL::queryRecordset failure running $query");
            return false;
        }
        return $recordset;
    }

    /**
     * This is an strict mode of queryRecordset but throws an exception instead of return FALSE
     *
     * @param string $query
     * @param string $overrideEntity
     * @param string[] $overrideKeys
     * @param array<string, string> $overrideTypes
     * @see DBAL::queryRecordset()
     * @throws QueryException if some error occurs when creating the Recordset object or getting the page
     * @return Recordset
     */
    final public function createRecordset(
        string $query,
        string $overrideEntity = '',
        array $overrideKeys = [],
        array $overrideTypes = []
    ): Recordset {
        try {
            $recordset = new Recordset($this);
            $recordset->query($query, $overrideEntity, $overrideKeys, $overrideTypes);
        } catch (Throwable $exception) {
            throw new QueryException('Unable to create a valid Recordset', $query, 0, $exception);
        }
        return $recordset;
    }

    /**
     * Get a Pager Object from the query
     *
     * @param string $querySelect
     * @param string $queryCount
     * @param int $page if -1 then it will query all records (not paged)
     * @param int $recordsPerPage
     * @see DBAL::createPager()
     * @return Pager|false
     */
    final public function queryPager(
        string $querySelect,
        string $queryCount = '',
        int $page = 1,
        int $recordsPerPage = 20
    ) {
        try {
            return $this->createPager($querySelect, $queryCount, $page, $recordsPerPage);
        } catch (Throwable $exception) {
            $this->logger->error("DBAL::queryPager failure running $querySelect");
            return false;
        }
    }

    /**
     * This is an strict mode of queryPager but throws an exception instead of return FALSE
     *
     * @param string $querySelect
     * @param string $queryCount
     * @param int $page
     * @param int $recordsPerPage
     * @see DBAL::queryPager()
     * @throws QueryException if some error occurs when creating the Pager object or getting the page
     * @return Pager
     */
    final public function createPager(
        string $querySelect,
        string $queryCount = '',
        int $page = 1,
        int $recordsPerPage = 20
    ): Pager {
        $previous = null;
        try {
            $pager = new Pager($this, $querySelect, $queryCount);
            $pager->setPageSize($recordsPerPage);
            $success = (-1 == $page) ? $pager->queryAll() : $pager->queryPage($page);
            if (! $success) {
                $pager = false;
            }
        } catch (Throwable $exception) {
            $previous = $exception;
            $pager = false;
        }
        if (! ($pager instanceof Pager)) {
            throw new QueryException('Unable to create a valid Pager', $querySelect, 0, $previous);
        }

        return $pager;
    }

    /**
     * Get the last error message, empty string if it is not connected
     *
     * @return string
     */
    final public function getLastMessage(): string
    {
        if ($this->isConnected()) {
            return $this->getLastErrorMessage();
        }
        return '';
    }

    /**
     * Creates a QueryException with the last message, if no last message exists then uses 'Database error'
     *
     * @param string $query
     * @param Throwable|null $previous
     * @return QueryException
     */
    final public function createQueryException(string $query, Throwable $previous = null): QueryException
    {
        return new QueryException($this->getLastMessage() ?: 'Database error', $query, 0, $previous);
    }
}
