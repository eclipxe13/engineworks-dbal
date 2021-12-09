<?php

declare(strict_types=1);

namespace EngineWorks\DBAL;

use EngineWorks\DBAL\Exceptions\QueryException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Database Abstraction Layer Interface Class
 */
interface DBAL extends CommonTypes, LoggerAwareInterface
{
    public function getLogger(): LoggerInterface;

    /**
     * Try to connect to the database with the current configured options
     * If connected it will disconnect first
     *
     * @return bool true if the connection was made
     */
    public function connect(): bool;

    /**
     * Disconnect
     */
    public function disconnect(): void;

    /**
     * Return the state of the connection
     * @phpstan-impure
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Get the last inserted id, use it after an insert
     *
     * @return int
     */
    public function lastInsertedID(): int;

    /**
     * Return the current transaction level (managed by the object, not the database)
     *
     * @return int
     */
    public function getTransactionLevel(): int;

    /**
     * Start a transaction
     */
    public function transBegin(): void;

    /**
     * Commit a transaction
     */
    public function transCommit(): void;

    /**
     * Rollback a transaction
     */
    public function transRollback(): void;

    /**
     * This function prevent the upper transaction to commit
     * In case of commit the transCommitMehod will trigger an error and return without commit
     *
     * If argument $preventCommit is null then this function will return the current prevent commit state
     * Otherwise, It will set the prevention commit state to the argument value and return the previous value
     *
     * @param bool|null $preventCommit
     * @return bool
     */
    public function transPreventCommit(bool $preventCommit = null): bool;

    /**
     * Escapes a table name including its prefix and optionally renames it.
     * This is the same method as sqlTableEscape but using the table prefix from settings.
     * Optionaly renames it as an alias.
     *
     * @param string $tableName
     * @param string $asTable
     * @return string
     */
    public function sqlTable(string $tableName, string $asTable = ''): string;

    /**
     * Escapes a table name to not get confused with reserved words or invalid chars.
     * Optionaly renames it as an alias.
     *
     * @param string $tableName
     * @param string $asTable
     * @return string
     */
    public function sqlTableEscape(string $tableName, string $asTable = ''): string;

    /**
     * Return a field name
     * Optionaly renames it as an alias.
     * This function do not escape the field name.
     *
     * Use example: $dbal->sqlField('COUNT(*)', 'rows'); or $dbal->sqlField('name')
     *
     * @param string $fieldName
     * @param string $asFieldName
     * @return string
     * @see self::sqlFieldEscape
     */
    public function sqlField(string $fieldName, string $asFieldName = ''): string;

    /**
     * Escapes a table name to not get confused with reserved words or invalid chars.
     * Optionaly renames it as an alias.
     *
     * @param string $fieldName
     * @param string $asTable
     * @return string
     */
    public function sqlFieldEscape(string $fieldName, string $asTable = ''): string;

    /**
     * Parses a value to secure SQL
     *
     * @param mixed $variable
     * @param string $commonType
     * @param bool $includeNull
     * @return string
     */
    public function sqlQuote($variable, string $commonType = CommonTypes::TTEXT, bool $includeNull = false): string;

    /**
     * Parses values to secure SQL for IN operator
     *
     * @param mixed[] $values
     * @param string $commonType
     * @param bool $includeNull
     * @return string example "(1, 3, 5)"
     * @throws RuntimeException if the array of values is empty
     */
    public function sqlQuoteIn(
        array $values,
        string $commonType = CommonTypes::TTEXT,
        bool $includeNull = false
    ): string;

    /**
     * Return a comparison condition using IN operator
     * If the array of values is empty then create an always false condition "0 = 1"
     *
     * @param string $field
     * @param mixed[] $values
     * @param string $commonType
     * @param bool $positive Set FALSE to perform a NOT IN comparison
     * @param bool $includeNull
     *
     * @return string
     * @see sqlQuoteIn
     *
     */
    public function sqlIn(
        string $field,
        array $values,
        string $commonType = CommonTypes::TTEXT,
        bool $positive = true,
        bool $includeNull = false
    ): string;

    /**
     * Return a NEGATIVE comparison condition using IN operator
     * If the array of values is empty then create an always true condition "1 = 1"
     *
     * @param string $field
     * @param mixed[] $values
     * @param string $commonType
     * @param bool $includeNull
     *
     * @return string
     * @see sqlQuoteIn
     *
     */
    public function sqlNotIn(
        string $field,
        array $values,
        string $commonType = CommonTypes::TTEXT,
        bool $includeNull = false
    ): string;

    /**
     * Quote as string
     *
     * @param scalar $variable
     * @return string
     */
    public function sqlString($variable): string;

    /**
     * Random function
     *
     * @return string
     */
    public function sqlRandomFunc(): string;

    /**
     * Return a comparison condition against null
     *
     * @param string $field
     * @param bool $positive Set FALSE to perform an IS NOT NULL comparison
     * @return string
     */
    public function sqlIsNull(string $field, bool $positive = true): string;

    /**
     * Return a NEGATIVE comparison condition against null
     *
     * @param string $field
     * @return string
     */
    public function sqlIsNotNull(string $field): string;

    /**
     * Return a condition using between operator quoting lower and upper bounds
     *
     * @param string $field
     * @param mixed $lowerBound
     * @param mixed $upperBound
     * @param string $commonType
     * @return string
     */
    public function sqlBetweenQuote(
        string $field,
        $lowerBound,
        $upperBound,
        string $commonType = CommonTypes::TTEXT
    ): string;

    /**
     * If function
     *
     * @param string $condition
     * @param string $truePart
     * @param string $falsePart
     * @return string
     */
    public function sqlIf(string $condition, string $truePart, string $falsePart): string;

    /**
     * If null function
     *
     * @param string $fieldName
     * @param string $nullValue
     * @return string
     */
    public function sqlIfNull(string $fieldName, string $nullValue): string;

    /**
     * Transform a SELECT query to be paged
     * This function add a semicolon at the end of the sentence
     *
     * @param string $query
     * @param int $requestedPage
     * @param int $recordsPerPage
     * @return string
     */
    public function sqlLimit(string $query, int $requestedPage, int $recordsPerPage = 20): string;

    /**
     * Like operator (simple)
     *
     * @param string $fieldName
     * @param string $searchString
     * @param bool $wildcardBegin
     * @param bool $wildcardEnd
     * @return string
     */
    public function sqlLike(
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
    public function sqlLikeSearch(
        string $fieldName,
        string $searchTerms,
        bool $matchAnyTerm = true,
        string $termsSeparator = ' '
    ): string;

    /**
     * Concatenation, as this can allow fields and strings, all strings must be previously escaped
     *
     * @param string ...$strings fields and escaped strings
     * @return string
     */
    public function sqlConcatenate(...$strings): string;

    /**
     * Function to get a part of a date using sql formatting functions
     * Valid part are: FHMS (Format Hours Minutes Seconds), FDOM (First Day Of Month), FYM (Format Year Month),
     * FYMD (Format Year Month Date), YEAR, MONTH, DAY, HOUR, MINUTE & SECOND
     *
     * @param string $part
     * @param string $expression
     * @return string
     */
    public function sqlDatePart(string $part, string $expression): string;

    /**
     * Executes a query and return a Result
     *
     * @param string $query
     * @param array<string, string> $overrideTypes use this to override detected types
     * @return Result|false
     */
    public function queryResult(string $query, array $overrideTypes = []);

    /**
     * Executes a query and return the affected rows
     *
     * @param string $query
     * @param string $exceptionMessage Throws QueryException with message on error
     * @return int|false Number of affected rows or FALSE on error
     * @throws RuntimeException if the result is FALSE and the $exceptionMessage was set
     */
    public function execute(string $query, string $exceptionMessage = '');

    /**
     * Executes a query and return a Result
     *
     * @param string $query
     * @return Result|false
     * @deprecated since version 1.5.0 in favor of queryResult
     * @see queryResult
     * @access private
     */
    public function query(string $query);

    /**
     * Get the first field in row of a query
     *
     * @param string $query
     * @param scalar|null $default
     * @return scalar|null
     */
    public function queryOne(string $query, $default = false);

    /**
     * Get the first row of a query
     *
     * @param string $query
     * @return array<string, scalar|null>|false
     */
    public function queryRow(string $query);

    /**
     * Get the first row of a query, the values are in common types
     *
     * @param string $query
     * @param array<string, string> $overrideTypes
     * @return array<string, scalar|null>|false
     */
    public function queryValues(string $query, array $overrideTypes = []);

    /**
     * Get an array of rows of a query
     *
     * @param string $query
     * @return array<int, array<string, scalar|null>>|false
     */
    public function queryArray(string $query);

    /**
     * Get an array of rows of a query, the values are in common types
     *
     * @param string $query
     * @param array<string, string> $overrideTypes
     * @return array<int, array<string, scalar|null>>|false
     */
    public function queryArrayValues(string $query, array $overrideTypes = []);

    /**
     * Get an array of rows of a query
     * It uses the keyField as index of the array
     *
     * @param string $query
     * @param string $keyField
     * @param string $keyPrefix
     * @return array<array<string, scalar|null>>|false
     */
    public function queryArrayKey(string $query, string $keyField, string $keyPrefix = '');

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
     * @param scalar|null $default
     * @return array<scalar|null>
     */
    public function queryPairs(
        string $query,
        string $keyField,
        string $valueField,
        string $keyPrefix = '',
        $default = false
    ): array;

    /**
     * Return one dimensional array with the values of one column of the query
     * If the field is not set then the function will take the first column
     *
     * @param string $query
     * @param string $field
     * @return array<int, scalar|null>|false
     */
    public function queryArrayOne(string $query, string $field = '');

    /**
     * Return the result of the query as an imploded string with all the values of the first column
     *
     * @param string $query
     * @param string $default
     * @param string $separator
     * @return string
     */
    public function queryOnString(string $query, string $default = '', string $separator = ', '): string;

    /**
     * Returns a Recordset Object from the query, false if error
     *
     * @param string $query
     * @param string $overrideEntity
     * @param string[] $overrideKeys
     * @param array<string, string> $overrideTypes
     * @return Recordset|false
     */
    public function queryRecordset(
        string $query,
        string $overrideEntity = '',
        array $overrideKeys = [],
        array $overrideTypes = []
    );

    /**
     * This is a strict mode of queryRecordset but throws an exception instead of return FALSE
     *
     * @param string $query
     * @param string $overrideEntity
     * @param string[] $overrideKeys
     * @param array<string, string> $overrideTypes
     * @return Recordset
     * @throws QueryException if some error occurs when creating the Recordset object or getting the page
     * @see DBAL::queryRecordset()
     */
    public function createRecordset(
        string $query,
        string $overrideEntity = '',
        array $overrideKeys = [],
        array $overrideTypes = []
    ): Recordset;

    /**
     * Get a Pager Object from the query
     *
     * @param string $querySelect
     * @param string $queryCount
     * @param int $page if -1 then it will query all records (not paged)
     * @param int $recordsPerPage
     * @return Pager|false
     * @see DBAL::createPager()
     */
    public function queryPager(string $querySelect, string $queryCount = '', int $page = 1, int $recordsPerPage = 20);

    /**
     * This is a strict mode of queryPager but throws an exception instead of return FALSE
     *
     * @param string $querySelect
     * @param string $queryCount
     * @param int $page
     * @param int $recordsPerPage
     * @return Pager
     * @throws QueryException if some error occurs when creating the Pager object or getting the page
     * @see DBAL::queryPager()
     */
    public function createPager(
        string $querySelect,
        string $queryCount = '',
        int $page = 1,
        int $recordsPerPage = 20
    ): Pager;

    /**
     * Get the last error message, empty string if it is not connected
     *
     * @return string
     */
    public function getLastMessage(): string;

    /**
     * Creates a QueryException with the last message, if no last message exists then uses 'Database error'
     *
     * @param string $query
     * @param Throwable|null $previous
     * @return QueryException
     */
    public function createQueryException(string $query, Throwable $previous = null): QueryException;
}
