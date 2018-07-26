<?php
namespace EngineWorks\DBAL;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Database Abstraction Layer Abstract Class
 */
abstract class DBAL implements CommonTypes, LoggerAwareInterface
{
    /* -----
     * Logger
     */
    use LoggerAwareTrait;

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /* -----
     * protected variables about the object
     */

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

    /* -----
     * magic methods
     */

    /**
     * @param Settings $settings
     * @param LoggerInterface|null $logger If null then a NullLogger will be used
     */
    public function __construct(Settings $settings, LoggerInterface $logger = null)
    {
        $this->settings = $settings;
        $this->setLogger((null === $logger) ? new NullLogger() : $logger);
    }

    /**
     * Destructor - force to call disconnect
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /* -----
     * public methods (to override)
     */

    /**
     * Try to connect to the database with the current configured options
     * If connected it will disconnect first
     *
     * @return bool true if the connection was made
     */
    abstract public function connect();

    /**
     * Disconnect
     * @return void
     */
    abstract public function disconnect();

    /**
     * Return the state of the connection
     * @return bool
     */
    abstract public function isConnected();

    /**
     * Get the last inserted id, use it after an insert
     * @return float
     */
    abstract public function lastInsertedID();

    /**
     * Implement the transaction begin command
     * @return void
     */
    protected function commandTransactionBegin()
    {
        $this->execute('BEGIN TRANSACTION', 'Cannot start transaction');
    }

    /**
     * Implement the transaction commit command
     * @return void
     */
    protected function commandTransactionCommit()
    {
        $this->execute('COMMIT', 'Cannot commit transaction');
    }

    /**
     * Implement the transaction rollback command
     * @return void
     */
    protected function commandTransactionRollback()
    {
        $this->execute('ROLLBACK', 'Cannot rollback transaction');
    }

    /**
     * Implement the savepoint command
     * @param string $name
     * @return void
     */
    protected function commandSavepoint($name)
    {
        $this->execute("SAVEPOINT $name", "Cannot create savepoint $name");
    }

    /**
     * Implement the release savepoint command
     * @param string $name
     * @return void
     */
    protected function commandReleaseSavepoint($name)
    {
        $this->execute("RELEASE SAVEPOINT $name", "Cannot release savepoint $name");
    }

    /**
     * Implement the rollback to savepoint command
     * @param string $name
     * @return void
     */
    protected function commandRollbackToSavepoint($name)
    {
        $this->execute("ROLLBACK TO SAVEPOINT $name", "Cannot rollback to savepoint $name");
    }

    /**
     * Return the current transaction level (managed by the object, not the database)
     * @return int
     */
    final public function getTransactionLevel()
    {
        return $this->transactionLevel;
    }

    /**
     * start a transaction
     */
    final public function transBegin()
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
    final public function transCommit()
    {
        $this->logger->info('-- TRANSACTION COMMIT');
        // reduce the transaction level
        if ($this->transactionLevel === 0) {
            trigger_error('Try to call commit without a transaction', E_USER_NOTICE);
            return;
        }
        $this->transactionLevel = $this->transactionLevel - 1;
        // do commit or savepoint
        if (0 === $this->transactionLevel) {
            if ($this->transPreventCommit()) {
                $this->transactionLevel = 1;
                trigger_error('Try to call final commit with prevent commit enabled', E_USER_ERROR);
                return;
            }
            $this->commandTransactionCommit();
        } else {
            $this->commandReleaseSavepoint("LEVEL_{$this->transactionLevel}");
        }
    }

    /**
     * rollback a transaction
     */
    final public function transRollback()
    {
        $this->logger->info('-- TRANSACTION ROLLBACK ');
        // reduce the transaction level
        if ($this->transactionLevel === 0) {
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
     * @throws \UnexpectedValueException If the $preventCommit argument is not boolean
     */
    final public function transPreventCommit($preventCommit = null)
    {
        if (null === $preventCommit) {
            return $this->preventCommit;
        }
        if (! is_bool($preventCommit)) {
            throw new \UnexpectedValueException('The prevent commit argument is not boolean');
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
    final public function sqlTable($tableName, $asTable = '')
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
    abstract public function sqlTableEscape($tableName, $asTable = '');

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
    final public function sqlField($fieldName, $asFieldName = '')
    {
        return $fieldName . (('' !== $asFieldName) ? ' AS ' . $this->sqlFieldEscape($asFieldName) : '');
    }

    /**
     * Escapes a table name to not get confused with reserved words or invalid chars.
     * Optionaly renames it as an alias.
     *
     * @param string $tableName
     * @param string $asTable
     * @return string
     */
    abstract public function sqlFieldEscape($tableName, $asTable = '');

    /**
     * Parses a value to secure SQL
     *
     * @param mixed $variable
     * @param string $commonType
     * @param bool $includeNull
     * @return string
     */
    abstract public function sqlQuote($variable, $commonType = CommonTypes::TTEXT, $includeNull = false);

    /**
     * Parses values to secure SQL for IN operator
     *
     * @param array $array
     * @param string $commonType
     * @param bool $includeNull
     * @return string|false example "(1, 3, 5)", false if the array is empty
     */
    final public function sqlQuoteIn(array $array, $commonType = CommonTypes::TTEXT, $includeNull = false)
    {
        if (count($array) == 0) {
            return false;
        }
        $values = array_unique($array);
        foreach ($values as $index => $value) {
            $values[$index] = $this->sqlQuote($value, $commonType, $includeNull);
        }
        $return = implode(', ', $values);
        return '(' . $return . ')';
    }

    /**
     * Quote as string
     *
     * @param string $variable
     * @return string
     */
    abstract public function sqlString($variable);

    /**
     * Random function
     * @return string
     */
    abstract public function sqlRandomFunc();

    /**
     * Comparison is null
     * @param string $field
     * @param bool $positive If set tu FALSE perform a IS NOT NULL comparison
     * @return string
     */
    abstract public function sqlIsNull($field, $positive = true);

    /**
     * If function
     * @param string $condition
     * @param string $truePart
     * @param string $falsePart
     * @return string
     */
    abstract public function sqlIf($condition, $truePart, $falsePart);

    /**
     * If null function
     * @param string $fieldName
     * @param string $nullValue
     * @return string
     */
    abstract public function sqlIfNull($fieldName, $nullValue);

    /**
     * Transform a SELECT query to be paged
     * This function add a semicolon at the end of the sentence
     *
     * @param string $query
     * @param int $requestedPage
     * @param int $recordsPerPage
     * @return string
     */
    abstract public function sqlLimit($query, $requestedPage, $recordsPerPage = 20);

    /**
     * like operator (simple)
     * @param string $fieldName
     * @param string $searchString
     * @param bool $wildcardBegin
     * @param bool $wildcardEnd
     * @return string
     */
    abstract public function sqlLike($fieldName, $searchString, $wildcardBegin = true, $wildcardEnd = true);

    /**
     * like operator (advanced)
     * @param string $fieldName
     * @param string $searchString
     * @param bool $someWords
     * @param string $separator
     * @return string
     */
    final public function sqlLikeSearch($fieldName, $searchString, $someWords = true, $separator = ' ')
    {
        $conditions = [];
        if (! is_string($searchString)) {
            return '';
        }
        $strings = array_filter(explode($separator, $searchString));
        foreach ($strings as $term) {
            $conditions[] = '(' . $this->sqlLike($fieldName, $term) . ')';
        }
        return implode(($someWords) ? ' OR ' : ' AND ', $conditions);
    }

    /**
     * Concatenation, as this can allow fields and strings, all strings must be previously escaped
     * @param string[] ...$strings fields and escaped strings
     * @return string
     */
    abstract public function sqlConcatenate(...$strings);

    /**
     * Function to get a part of a date using sql formatting functions
     * Valid part are: YEAR, MONTH, FDOM (First Day Of Month), FYM (Format Year Month),
     * FYMD (Format Year Month Date), DAY, HOUR. MINUTE, SECOND
     * @param string $part
     * @param string $expression
     * @return string
     */
    abstract public function sqlDatePart($part, $expression);

    /* -----
     * protected methods (to override)
     */

    /**
     * Executes a query and return a Result
     * @param string $query
     * @param array $overrideTypes use this to override detected types
     * @return Result|false
     */
    abstract public function queryResult($query, array $overrideTypes = []);

    /**
     * Executes a query and return the number of affected rows
     *
     * @param string $query
     * @return int|false FALSE if the query fails
     */
    abstract protected function queryAffectedRows($query);

    /**
     * Return the last error message, always should return a message
     * @return string
     */
    abstract protected function getLastErrorMessage();

    /* -----
     * public methods (finals, not to override)
     */

    /**
     * Executes a query and return the affected rows
     * @param string $query
     * @param string $exceptionMessage
     * @return int|bool Number of affected rows or FALSE on error
     * @throws \RuntimeException if the result is FALSE and the $exceptionMessage was set
     */
    final public function execute($query, $exceptionMessage = '')
    {
        $return = $this->queryAffectedRows($query);
        if (false === $return) {
            if ('' !== $exceptionMessage) {
                $previous = $this->getLastErrorMessage() ? new \RuntimeException($this->getLastErrorMessage()) : null;
                throw new \RuntimeException($exceptionMessage, 0, $previous);
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
    final public function query($query)
    {
        trigger_error(__METHOD__ . ' is deprecated, use queryResult instead', E_USER_DEPRECATED);
        return $this->queryResult($query);
    }

    /**
     * Get the first field an( row of a query
     * Returns false if error or empty
     * @param string $query
     * @param mixed $default
     * @return mixed
     */
    final public function queryOne($query, $default = false)
    {
        return current($this->queryRow($query) ?: [$default]);
    }

    /**
     * Get the first row of a query
     * Returns false if error or empty row
     * @param string $query
     * @return array|false
     */
    final public function queryRow($query)
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
     * Returns false if error or empty row
     * @param string $query
     * @return array|false
     */
    final public function queryValues($query)
    {
        $recordset = $this->queryRecordset($query);
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
     * Returns false if error
     * @param string $query
     * @return array|false
     */
    final public function queryArray($query)
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
     * Returns false if error
     * @param string $query
     * @return array|false
     */
    final public function queryArrayValues($query)
    {
        $recordset = $this->queryRecordset($query);
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
     * Returns false if error
     * @param string $query
     * @param string $keyField
     * @param string $keyPrefix
     * @return array|false
     */
    final public function queryArrayKey($query, $keyField, $keyPrefix = '')
    {
        $result = $this->queryResult($query);
        if (false === $result) {
            return false;
        }

        $return = [];
        while (false !== $row = $result->fetchRow()) {
            if (! array_key_exists($keyField, $row)) {
                return false;
            }
            $return[strval($keyPrefix . $row[$keyField])] = $row;
        }
        return $return;
    }

    /**
     * Return a one dimension array with keys and values defined by keyField and valueField
     * The resulting array keys can have a prefix defined by keyPrefix
     * If two keys collapse then the last value will be used
     * Always return an array, even if fail
     * @param string $query
     * @param string $keyField
     * @param string $valueField
     * @param string $keyPrefix
     * @param mixed $default
     * @return array
     */
    final public function queryPairs($query, $keyField, $valueField, $keyPrefix = '', $default = false)
    {
        $return = [];
        $array = $this->queryArray($query) ?: [];
        foreach ($array as $row) {
            $return[$keyPrefix . ($row[$keyField] ?? '')] = $row[$valueField] ?? $default;
        }
        return $return;
    }

    /**
     * Return one dimensional array with the values of one column of the query
     * if the field is not set then the function will take the first column
     * @param string $query
     * @param string $field
     * @return array|false
     */
    final public function queryArrayOne($query, $field = '')
    {
        $result = $this->queryResult($query);
        if (false === $result) {
            return false;
        }

        $return = [];
        while (false !== $row = $result->fetchRow()) {
            if ('' === $field) {
                $keys = array_keys($row);
                $field = $keys[0];
            }
            $return[] = $row[$field];
        }
        return $return;
    }

    /**
     * Return the result of the query as a imploded string with all the values of the first column
     * @param string $query
     * @param string $default
     * @param string $separator
     * @return string
     */
    final public function queryOnString($query, $default = '', $separator = ', ')
    {
        $array = $this->queryArrayOne($query);
        if (false === $array) {
            return $default;
        }
        return implode($separator, $array);
    }

    /**
     * Returns a Recordset Object from the query, false if error
     * @param string $query
     * @param string $overrideEntity
     * @param string[] $overrideKeys
     * @param string[] $overrideTypes
     * @return Recordset|false
     */
    final public function queryRecordset(
        $query,
        $overrideEntity = '',
        array $overrideKeys = [],
        array $overrideTypes = []
    ) {
        $recordset = new Recordset($this);
        if (! $recordset->query($query, $overrideEntity, $overrideKeys, $overrideTypes)) {
            $this->logger->error("DBAL::queryRecorset failure running $query");
            return false;
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
     * @return Pager|false
     */
    final public function queryPager($querySelect, $queryCount = '', $page = 1, $recordsPerPage = 20)
    {
        $pager = new Pager($this, $querySelect, $queryCount);
        $pager->setPageSize($recordsPerPage);
        $success = ($page == -1) ? $pager->queryAll() : $pager->queryPage($page);
        if (! $success) {
            $this->logger->error("DBAL::queryPager failure running $querySelect");
            return false;
        }
        return $pager;
    }

    /**
     * Get the last error message, false if none
     * @return string
     */
    final public function getLastMessage()
    {
        if ($this->isConnected()) {
            return $this->getLastErrorMessage();
        }
        return '';
    }
}
