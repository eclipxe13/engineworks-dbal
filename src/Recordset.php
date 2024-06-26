<?php

declare(strict_types=1);

namespace EngineWorks\DBAL;

use Countable;
use EngineWorks\DBAL\Internal\ConvertObjectToStringMethod;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Recordset class
 * Hint: Use DBAL::queryRecordset() instead of using this class directly
 * @implements IteratorAggregate<int|string, array<string, mixed>>
 */
class Recordset implements LoggerAwareInterface, IteratorAggregate, Countable
{
    use ConvertObjectToStringMethod;

    public const RSMODE_NOTCONNECTED = 0;

    public const RSMODE_CONNECTED_EDIT = 1;

    public const RSMODE_CONNECTED_ADDNEW = 2;

    /**
     * Associative array of the current record
     * @var array<string, scalar|null>
     */
    public $values;

    /** @var DBAL */
    private $dbal;

    /**
     * Never use this property, use self::getLogger() instead because
     * when Logger is NULL it uses the DBAL::getLogger().
     *
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @var Result|null
     */
    private $result;

    /**
     * Array of original values
     * @var array<string, scalar|null>|null
     */
    private $originalValues;

    /**
     * source sql query
     * @var string
     */
    private $source;

    /** @var int */
    private $mode;

    /**
     * Has the name of the current entity
     * @var string
     */
    private $entity;

    /**
     * This array is a local copy of $this->result->getFields()
     * @var array<string, array{name: string, table: string, commontype: string}>
     */
    private $datafields;

    /**
     * Storage of idFields, set after call query method
     * @var string[]
     */
    private $idFields;

    /**
     * Recordset constructor.
     *
     * @param DBAL $dbal
     * @param LoggerInterface|null $logger If not provided it uses the DBAL Logger
     */
    public function __construct(DBAL $dbal, LoggerInterface $logger = null)
    {
        $this->dbal = $dbal;
        $this->logger = $logger;
        $this->initialize();
    }

    /**
     * Return the current logger, uses the DBAL logger when local logger is not set
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger ?? $this->dbal->getLogger();
    }

    /**
     * Define the current logger, if NULL uses the DBAL logger
     *
     * @param LoggerInterface|null $logger
     */
    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Executes a SQL Query and connect the object with that query in order to operate the recordset
     * @param string $sql
     * @param string $overrideEntity
     * @param string[] $overrideKeys
     * @param string[] $overrideTypes
     *
     * @return true
     */
    final public function query(
        string $sql,
        string $overrideEntity = '',
        array $overrideKeys = [],
        array $overrideTypes = []
    ): bool {
        $this->initialize();
        if (! $this->hasDBAL()) {
            throw new LogicException('Recordset: object does not have a connected DBAL');
        }
        $result = $this->dbal->queryResult($sql, $overrideTypes);
        if (! $result instanceof Result) {
            throw new LogicException("Recordset: Unable to perform query $sql");
        }
        $this->mode = self::RSMODE_CONNECTED_EDIT;
        $this->result = $result;
        $this->source = $sql;
        $this->datafields = [];
        // get fields into a temporary array
        /** @var array<array{name: string, table: string, commontype: string}> $tmpfields */
        $tmpfields = $this->result()->getFields();
        foreach ($tmpfields as $tmpfield) {
            $this->datafields[$tmpfield['name']] = $tmpfield;
        }
        // set the entity name, remove if more than one table exists
        if (count(array_unique(array_column($tmpfields, 'table'))) > 1) {
            $this->entity = '';
        } else {
            $this->entity = (string) $tmpfields[0]['table'];
        }
        if ('' !== $overrideEntity) {
            $this->entity = $overrideEntity;
        }
        // set the id fields if did not override
        if ([] === $overrideKeys) {
            $this->idFields = $this->result()->getIdFields() ?: [];
        } else {
            // validate overrideKeys
            if ($overrideKeys !== array_unique($overrideKeys)) {
                throw new InvalidArgumentException('Keys contains repeated values');
            }
            foreach ($overrideKeys as $fieldName) {
                if (! is_string($fieldName)) {
                    throw new InvalidArgumentException('Keys were set but at least one is not a string');
                }
                if (! array_key_exists($fieldName, $this->datafields)) {
                    throw new InvalidArgumentException(
                        "The field name $fieldName does not exists in the set of fields"
                    );
                }
            }
            $this->idFields = $overrideKeys;
        }
        // if it has records then load first
        if ($this->getRecordCount() > 0) {
            $this->moveNext();
        }
        return true;
    }

    /**
     * Internal procedure to initiate all the variables
     */
    private function initialize(): void
    {
        $this->entity = '';
        $this->source = '';
        $this->mode = self::RSMODE_NOTCONNECTED;
        $this->result = null;
        $this->originalValues = null;
        $this->datafields = [];
        $this->values = [];
        $this->idFields = [];
    }

    /**
     * Return if the current DBAL and Result exists and are connected
     * @return bool
     */
    final public function isOpen(): bool
    {
        return ($this->hasDBAL() && null !== $this->result);
    }

    /**
     * Check if the DBAL instance is connected (if not try to connect again)
     * @return bool
     */
    final public function hasDBAL(): bool
    {
        return ($this->dbal->isConnected() || $this->dbal->connect());
    }

    /**
     * Return the source query
     * @return string
     */
    final public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Return the recordset mode
     * @return int
     */
    final public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * Return if the current recordset can be edited
     * @return bool
     */
    public function canModify(): bool
    {
        return (self::RSMODE_NOTCONNECTED !== $this->mode && '' !== $this->entity);
    }

    /**
     * Return if the recordset is placed in a valid record
     * @phpstan-impure
     * @return bool
     */
    final public function eof(): bool
    {
        return (! is_array($this->originalValues));
    }

    /**
     * Return the original value of a field
     * @param scalar|null $defaultValue
     * @return scalar|null
     */
    final public function getOriginalValue(string $fieldName, $defaultValue = '')
    {
        if (! is_array($this->originalValues) || ! array_key_exists($fieldName, $this->originalValues)) {
            return $defaultValue;
        }
        return $this->originalValues[$fieldName];
    }

    /**
     * Return an array with the original values.
     *
     * @return array<string, scalar|null>
     * @throws RuntimeException There are no original values
     */
    final public function getOriginalValues(): array
    {
        if (! is_array($this->originalValues)) {
            throw new RuntimeException('There are no original values');
        }
        return $this->originalValues;
    }

    /**
     * Prepares the recordset to make an insertion
     * All the values are set to null
     */
    final public function addNew(): void
    {
        $this->originalValues = null;
        $this->values = $this->emptyValuesFromDataFields();
        $this->mode = self::RSMODE_CONNECTED_ADDNEW;
    }

    /**
     * Get the last inserted id by asking the DBAL object.
     * This means that if an insertion happends between Update and LastInsertedID then the result
     * will not be related to the Update
     * @return int
     */
    final public function lastInsertedID(): int
    {
        return $this->dbal->lastInsertedID();
    }

    /**
     * Check whether the current values are different from the original ones
     * The base are the original values
     * @return bool
     * @throws RuntimeException if no original values exists
     */
    final public function valuesHadChanged(): bool
    {
        if (! is_array($this->originalValues)) {
            throw new RuntimeException('The recordset does not contain any original values');
        }
        foreach ($this->originalValues as $field => $value) {
            $current = array_key_exists($field, $this->values) ? $this->values[$field] : null;
            if (static::valueIsDifferent($value, $current)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Compare values in order to see if they need to be updated
     * @param object|scalar|null $original
     * @param object|scalar|null $current
     * @return bool
     */
    final protected static function valueIsDifferent($original, $current): bool
    {
        // check if some value is null
        $originalIsNull = (null === $original);
        $currentIsNull = (null === $current);
        // both are null, there are no difference
        if ($originalIsNull && $currentIsNull) {
            return false;
        }
        // one is null, the other isn't, there is a difference
        if ($originalIsNull || $currentIsNull) {
            return true;
        }
        // do not continue using the object, convert to string
        if (is_object($current)) {
            $current = self::convertObjectToString($current);
        }
        // strict comparison if types are strings
        if (is_string($original) && is_string($current)) {
            return ($original !== $current);
        }
        // simple comparison
        return ($original != $current);
    }

    /**
     * @return string[]
     */
    public function getIdFields(): array
    {
        return $this->idFields;
    }

    /**
     * Create an array of conditions based on the current values and ids
     * This function is used on Update and on Delete
     * @param string $extraWhereClause
     * @return string[]
     */
    protected function sqlWhereConditions(string $extraWhereClause): array
    {
        // get the conditions
        $conditions = [];
        if ($extraWhereClause) {
            $conditions[] = "($extraWhereClause)";
        }
        $ids = $this->getIdFields();
        if ([] === $ids) {
            $this->getLogger()->warning(sprintf(
                'Recordset: the where clause will be based on all fields because cannot locate ids.%s',
                "\n" . print_r(['entity' => $this->entity, 'values' => $this->values], true)
            ));
            $ids = array_keys($this->datafields);
        }
        foreach ($this->datafields as $fieldname => $field) {
            if (! array_key_exists($fieldname, $this->values)) {
                continue;
            }
            if (! in_array($fieldname, $ids)) {
                continue;
            }
            $originalValue = $this->getOriginalValue($fieldname, null);
            if (null === $originalValue) {
                $conditions[] = '(' . $this->dbal->sqlIsNull($this->dbal->sqlFieldEscape($fieldname)) . ')';
            } else {
                $conditions[] = '(' . $this->dbal->sqlFieldEscape($fieldname) . ' = '
                    . $this->dbal->sqlQuote($originalValue, $field['commontype'], false) . ')';
            }
        }
        return $conditions;
    }

    /**
     * Create the sql statement for INSERT INTO
     * @return string
     */
    protected function sqlInsert(): string
    {
        $inserts = [];
        foreach ($this->datafields as $fieldname => $field) {
            $value = (array_key_exists($fieldname, $this->values)) ? $this->values[$fieldname] : null;
            $escapedFieldName = $this->dbal->sqlFieldEscape($field['name']);
            $inserts[$escapedFieldName] = $this->dbal->sqlQuote($value, $field['commontype'], true);
        }
        if ([] === $inserts) {
            throw new LogicException('Recordset: Insert does not have any fields to insert');
        }
        return 'INSERT INTO ' . $this->dbal->sqlTableEscape($this->entity)
        . ' (' . implode(', ', array_keys($inserts)) . ')'
        . ' VALUES (' . implode(', ', $inserts) . ')'
        . ';';
    }

    /**
     * Create the sql statement for UPDATE
     * If nothing to update then will return an empty string
     * @param string $extraWhereClause
     * @return string
     */
    protected function sqlUpdate(string $extraWhereClause): string
    {
        // get the conditions to alter the current record
        $conditions = $this->sqlWhereConditions($extraWhereClause);
        // if no conditions then log error and return false
        if ([] === $conditions) {
            throw new LogicException('Recordset: The current record does not have any conditions to update');
        }
        // get the fields that have changed compared to originalValues
        $updates = [];
        foreach ($this->datafields as $fieldname => $field) {
            if (! array_key_exists($fieldname, $this->values)) {
                $this->values[$fieldname] = null;
            }
            if (static::valueIsDifferent($this->getOriginalValue($fieldname, null), $this->values[$fieldname])) {
                $updates[] = $this->dbal->sqlFieldEscape($fieldname) . ' = '
                    . $this->dbal->sqlQuote($this->values[$fieldname], $field['commontype'], true);
            }
        }
        // if nothing to update, log error and return empty string
        if ([] === $updates) {
            return '';
        }
        // return the update statement
        return 'UPDATE'
            . ' ' . $this->dbal->sqlTableEscape($this->entity)
            . ' SET ' . implode(', ', $updates)
            . ' WHERE ' . implode(' AND ', $conditions)
            . ';';
    }

    /**
     * Create the sql statement for DELETE
     * @param string $extraWhereClause
     * @return string
     */
    protected function sqlDelete(string $extraWhereClause): string
    {
        // get the conditions to alter the current record
        $conditions = $this->sqlWhereConditions($extraWhereClause);
        // if no conditions then log error and return false
        if ([] === $conditions) {
            throw new LogicException('Recordset: The current record does not have any conditions to delete');
        }
        return 'DELETE'
            . ' FROM ' . $this->dbal->sqlTableEscape($this->entity)
            . ' WHERE ' . implode(' AND ', $conditions)
            . ';';
    }

    /**
     * Build and execute an SQL UPDATE or INSERT sentence
     * Return how many rows where altered, if an update does not change any value then it returns zero
     * Return false in case of error execution
     *
     * @param string $extraWhereClause where clause to be appended into sql on UPDATE (not insert)
     * @return int|false
     */
    final public function update(string $extraWhereClause = '')
    {
        // check the current mode is on ADDNEW or EDIT
        if (self::RSMODE_CONNECTED_ADDNEW !== $this->mode && self::RSMODE_CONNECTED_EDIT !== $this->mode) {
            throw new LogicException(
                "Recordset: The recordset is not on edit or addnew mode [current: {$this->mode}]"
            );
        }
        // check the entity is not empty
        if ('' === $this->entity) {
            throw new LogicException('Recordset: The recordset does not have a valid unique entity');
        }
        $sql = '';
        if (self::RSMODE_CONNECTED_ADDNEW == $this->mode) {
            $sql = $this->sqlInsert();
        }
        if (self::RSMODE_CONNECTED_EDIT == $this->mode) {
            if ('' === $sql = $this->sqlUpdate($extraWhereClause)) {
                return 0;
            }
        }
        $altered = $this->dbal->execute($sql);
        if (0 === $altered) {
            $diffs = [];
            if (is_array($this->originalValues)) {
                foreach ($this->originalValues as $name => $value) {
                    if (! static::valueIsDifferent($value, $this->values[$name])) {
                        continue;
                    }
                    $diffs[] = $name;
                }
            }
            $this->getLogger()->warning(print_r([
                'message' => "Recordset: The statement $sql return zero affected rows but the values are different",
                'entity' => $this->entity,
                'extraWhereClause' => $extraWhereClause,
                'original' => $this->originalValues,
                'current' => $this->values,
                'diffs' => $diffs,
            ], true));
        }
        return $altered;
    }

    /**
     * Build and execute the SQL DELETE sentence
     * Return how many rows where altered
     *
     * @return int|false
     */
    final public function delete(string $extraWhereClause = '')
    {
        if (self::RSMODE_CONNECTED_EDIT !== $this->mode) {
            throw new LogicException('Recordset: The recordset is not on edit mode [current: ' . $this->mode . ']');
        }
        // check the entity is not empty
        if ('' === $this->entity) {
            throw new LogicException('Recordset: The recordset does not have a valid unique entity');
        }
        $sql = $this->sqlDelete($extraWhereClause);
        $altered = $this->dbal->execute($sql);
        if (0 === $altered) {
            $this->getLogger()->warning(print_r([
                'message' => "Recordset: The statement '$sql' return zero affected rows"
                    . ' but it should delete at least one record',
                'entity' => $this->entity,
                'extraWhereClause' => $extraWhereClause,
                'original' => $this->originalValues,
            ], true));
        }
        return $altered;
    }

    /**
     * Move to the next row and read the values
     * @return bool
     */
    final public function moveNext(): bool
    {
        return ($this->isOpen() && $this->fetchLoadValues());
    }

    /**
     * Move to the first row and read the values
     * @return bool
     */
    final public function moveFirst(): bool
    {
        return ($this->isOpen() && $this->result()->moveFirst() && $this->fetchLoadValues());
    }

    /**
     * Internal function that returns an array with the content from fields and row
     * @return array<string, null>
     */
    private function emptyValuesFromDataFields(): array
    {
        return array_fill_keys(array_keys($this->datafields), null);
    }

    /**
     * Internal function that returns an array with the content of all datafields
     * filled with the values cast
     *
     * @param array<string, scalar|null> $source
     * @return array<string, scalar|null>
     */
    private function setValuesFromDatafields(array $source): array
    {
        $values = [];
        foreach ($this->datafields as $fieldname => $field) {
            $values[$fieldname] = $this->castValueWithCommonType(
                array_key_exists($fieldname, $source) ? $source[$fieldname] : null,
                $field['commontype']
            );
        }
        return $values;
    }

    /**
     * Cast a generic value from the source to a typed value, if null return null
     *
     * @param scalar|null $value
     * @param string $commonType
     * @return scalar|null
     */
    protected function castValueWithCommonType($value, string $commonType)
    {
        // these are sorted by the most common data types to avoid extra comparisons
        if (null === $value) {
            return null;
        }
        if (CommonTypes::TTEXT === $commonType) {
            return strval($value);
        }
        if (CommonTypes::TINT === $commonType) {
            return intval($value);
        }
        if (CommonTypes::TNUMBER === $commonType) {
            return floatval($value);
        }
        if (CommonTypes::TBOOL === $commonType) {
            return boolval($value);
        }
        if (in_array($commonType, [CommonTypes::TDATE, CommonTypes::TTIME, CommonTypes::TDATETIME], true)) {
            return strtotime((string) $value);
        }
        return strval($value);
    }

    /**
     * Try to load values fetching a new row
     * Return true if success
     * Return false if no row was fetched, also put values to an empty array
     * @return bool
     */
    private function fetchLoadValues(): bool
    {
        $row = $this->result()->fetchRow();
        if (false === $row) {
            $this->originalValues = null;
            $this->values = [];
            return false;
        }
        $this->originalValues = $this->setValuesFromDatafields($row);
        $this->values = $this->originalValues;
        return true;
    }

    /**
     * Return the number of records in the query
     * @return int
     */
    final public function getRecordCount(): int
    {
        return $this->result()->resultCount();
    }

    /**
     * Return an associative array of fields, the key is the field name
     * and the content is an array containing name, common type and table
     * @return array<string, array{name: string, table: string, commontype: string}>
     */
    final public function getFields(): array
    {
        return $this->datafields;
    }

    /**
     * Return the entity name of the query
     * @return string
     */
    final public function getEntityName(): string
    {
        return $this->entity;
    }

    final public function count(): int
    {
        return $this->getRecordCount();
    }

    final public function getIterator(): Iterators\RecordsetIterator
    {
        return new Iterators\RecordsetIterator($this);
    }

    /**
     * @return Result
     */
    private function result(): Result
    {
        if (null === $this->result) {
            throw new RuntimeException('The current state of the result is NULL');
        }
        return $this->result;
    }
}
