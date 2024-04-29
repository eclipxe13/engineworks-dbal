<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Abstracts;

use BackedEnum;
use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Exceptions\QueryException;
use EngineWorks\DBAL\Internal\ConvertObjectToStringMethod;
use EngineWorks\DBAL\Internal\NumericParser;
use EngineWorks\DBAL\Pager;
use EngineWorks\DBAL\Recordset;
use EngineWorks\DBAL\Settings;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;
use UnitEnum;

abstract class BaseDBAL implements DBAL
{
    use ConvertObjectToStringMethod;

    /** @var LoggerInterface */
    protected $logger;

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
     * Contains the "prevent commit state" of transactions
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
     * Destructor - force call disconnect
     */
    public function __destruct()
    {
        $this->disconnect();
    }

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

    final public function getTransactionLevel(): int
    {
        return $this->transactionLevel;
    }

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

    final public function transPreventCommit(bool $preventCommit = null): bool
    {
        if (null === $preventCommit) {
            return $this->preventCommit;
        }
        $previous = $this->preventCommit;
        $this->preventCommit = $preventCommit;
        return $previous;
    }

    final public function sqlTable(string $tableName, string $asTable = ''): string
    {
        return $this->sqlTableEscape($this->settings->get('prefix', '') . $tableName, $asTable);
    }

    final public function sqlField(string $fieldName, string $asFieldName = ''): string
    {
        return $fieldName . (('' !== $asFieldName) ? ' AS ' . $this->sqlFieldEscape($asFieldName) : '');
    }

    final public function sqlQuote(
        $variable,
        string $commonType = CommonTypes::TTEXT,
        bool $includeNull = false
    ): string {
        if (PHP_VERSION_ID > 80100) { // PHP 8.1
            if ($variable instanceof UnitEnum) { // BackedEnum implements UnitEnum
                $variable = $variable instanceof BackedEnum ? $variable->value : $variable->name;
            }
        }
        if (is_object($variable)) {
            $variable = $this->convertObjectToString($variable);
        }
        if ($includeNull && null === $variable) {
            return 'NULL';
        }
        if (! is_scalar($variable) && ! is_null($variable)) {
            throw new InvalidArgumentException('Value is something that cannot be parsed as scalar');
        }
        // CommonTypes::TTEXT is here because is the most common used type
        if (CommonTypes::TTEXT === $commonType) {
            return "'" . $this->sqlString($variable ?? '') . "'";
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
        return "'" . $this->sqlString((string) $variable) . "'";
    }

    /**
     * @param scalar|null $value
     * @param bool $asInteger
     * @return string
     */
    private function sqlQuoteParseNumber($value, bool $asInteger): string
    {
        return (new NumericParser())->parseAsEnglish((string) $value, $asInteger);
    }

    final public function sqlQuoteIn(
        array $values,
        string $commonType = CommonTypes::TTEXT,
        bool $includeNull = false
    ): string {
        if ([] === $values) {
            throw new RuntimeException('The array of values passed to DBAL::sqlQuoteIn is empty');
        }
        return '('
            . implode(', ', array_map(function ($value) use ($commonType, $includeNull): string {
                return $this->sqlQuote($value, $commonType, $includeNull);
            }, array_unique($values)))
            . ')';
    }

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
        if ([] === $values) {
            return '0 = 1';
        }
        return $field . ' IN ' . $this->sqlQuoteIn($values, $commonType, $includeNull);
    }

    final public function sqlNotIn(
        string $field,
        array $values,
        string $commonType = CommonTypes::TTEXT,
        bool $includeNull = false
    ): string {
        if ([] === $values) {
            return '1 = 1';
        }
        return $field . ' NOT IN ' . $this->sqlQuoteIn($values, $commonType, $includeNull);
    }

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

    final public function sqlIsNotNull(string $field): string
    {
        return $field . ' IS NOT NULL';
    }

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

    final public function sqlIfNull(string $fieldName, string $nullValue): string
    {
        return 'IFNULL(' . $fieldName . ', ' . $nullValue . ')';
    }

    final public function sqlLikeSearch(
        string $fieldName,
        string $searchTerms,
        bool $matchAnyTerm = true,
        string $termsSeparator = ' '
    ): string {
        if ('' === $termsSeparator) {
            throw new InvalidArgumentException('Arguments to explode terms must not be an empty string');
        }
        return implode(
            ($matchAnyTerm) ? ' OR ' : ' AND ',
            array_map(function (string $term) use ($fieldName): string {
                return '(' . $this->sqlLike($fieldName, $term) . ')';
            }, array_unique(array_filter(explode($termsSeparator, $searchTerms))))
        );
    }

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

    final public function query(string $query)
    {
        trigger_error(__METHOD__ . ' is deprecated, use queryResult instead', E_USER_DEPRECATED);
        return $this->queryResult($query);
    }

    final public function queryOne(string $query, $default = false)
    {
        return current($this->queryRow($query) ?: [$default]);
    }

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

    final public function queryOnString(string $query, string $default = '', string $separator = ', '): string
    {
        $array = $this->queryArrayOne($query);
        if (false === $array) {
            return $default;
        }
        return implode($separator, $array);
    }

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

    final public function getLastMessage(): string
    {
        if ($this->isConnected()) {
            return $this->getLastErrorMessage();
        }
        return '';
    }

    final public function createQueryException(string $query, Throwable $previous = null): QueryException
    {
        return new QueryException($this->getLastMessage() ?: 'Database error', $query, 0, $previous);
    }

    final public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    final public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
