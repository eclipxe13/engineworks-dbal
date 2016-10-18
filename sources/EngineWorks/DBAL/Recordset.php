<?php
namespace EngineWorks\DBAL;

use Psr\Log\LoggerInterface;

/**
 * Recordset class
 * Hint: Use DBAL->queryRecordset instead of using this class directly
 */
class Recordset implements \IteratorAggregate, \Countable
{
    const RSMODE_NOTCONNECTED = 0;
    const RSMODE_CONNECTED_EDIT = 1;
    const RSMODE_CONNECTED_ADDNEW = 2;

    /**
     * Associative array of the current record
     * @var array
     */
    public $values;

    /**
     * @var DBAL
     */
    private $dbal;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @var Result
     */
    private $result;

    /**
     * Array of original values
     * @var array
     */
    private $originalValues;

    /**
     * source sql query
     * @var string
     */
    private $source;
    private $mode;

    /**
     * Has the name of the current entity
     * @var string
     */
    private $entity;

    /**
     * This array is a local copy of $this->result->getFields()
     * @var array
     */
    private $datafields;

    /**
     * Storage of idFields, set after call query method
     * @var string[]
     */
    private $idFields;

    /**
     * Recordset constructor.
     * @param DBAL $dbal
     * @param LoggerInterface|null $logger, If not provided it uses the DBAL Logger
     */
    public function __construct(DBAL $dbal, LoggerInterface $logger = null)
    {
        $this->dbal = $dbal;
        $this->logger = ($logger instanceof LoggerInterface) ? $logger : $dbal->getLogger();
        $this->initialize();
    }

    /**
     * Executes a SQL Query and connect the object with that query in order to operate the recordset
     * @param string $sql
     * @param string $overrideEntity
     * @param string[] $overrideKeys
     *
     * @return bool
     */
    final public function query($sql, $overrideEntity = '', array $overrideKeys = [])
    {
        $this->initialize();
        if (! $this->hasDBAL()) {
            throw new \LogicException('Recordset: object does not have a connected DBAL');
        }
        $result = $this->dbal->query($sql);
        if (! $result instanceof Result) {
            throw new \LogicException("Recordset: Unable to perform query $sql");
        }
        $this->mode = self::RSMODE_CONNECTED_EDIT;
        $this->result = $result;
        $this->source = $sql;
        $this->datafields = [];
        // get fields into a temporary array
        $tmpfields = $this->result->getFields();
        $tmpfieldsCount = count($tmpfields);
        for ($i = 0; $i < $tmpfieldsCount; $i++) {
            $this->datafields[$tmpfields[$i]['name']] = $tmpfields[$i];
        }
        // set the entity name, remove if more than one table exists
        if (count(array_unique(array_column($tmpfields, 'table'))) > 1) {
            $this->entity = '';
        } else {
            $this->entity = $tmpfields[0]['table'];
        }
        if ('' !== $overrideEntity) {
            $this->entity = $overrideEntity;
        }
        // set the id fields
        if (! count($overrideKeys)) {
            $this->idFields = ($this->result->getIdFields()) ? : [];
        } else {
            foreach ($overrideKeys as $fieldName) {
                if (! is_string($fieldName)) {
                    throw new \InvalidArgumentException('Keys were set but at least one is not a string');
                }
                if (! array_key_exists($fieldName, $this->datafields)) {
                    throw new \InvalidArgumentException(
                        "The field name $fieldName does not exists in the set of fields"
                    );
                }
            }
            $this->idFields = $overrideKeys;
        }
        // if has records then load first
        if ($this->getRecordCount() > 0) {
            $this->moveNext();
        }
        return true;
    }

    /**
     * Internal procedure to initiate all the variables
     */
    private function initialize()
    {
        $this->entity = '';
        $this->source = '';
        $this->mode = self::RSMODE_NOTCONNECTED;
        $this->result = null;
        $this->originalValues = null;
        $this->datafields = null;
        $this->values = [];
        $this->idFields = [];
    }

    /**
     * Return if the current DBAL and Result exists and are connected
     * @return bool
     */
    final public function isOpen()
    {
        return ($this->hasDBAL() and $this->result !== null);
    }

    /**
     * Check if the DBAL is is connected (if not try to connect again)
     * @return bool
     */
    final public function hasDBAL()
    {
        return ($this->dbal->isConnected() or $this->dbal->connect());
    }

    /**
     * Return the source query
     * @return string
     */
    final public function getSource()
    {
        return $this->source;
    }

    /**
     * Return the recordset mode
     * @return string
     */
    final public function getMode()
    {
        return $this->mode;
    }

    /**
     * Return if the current recordset can be edited
     * @return bool
     */
    public function canModify()
    {
        return ($this->mode !== self::RSMODE_NOTCONNECTED and '' !== $this->entity);
    }

    /**
     * Return if the recordset is placed in a valid record
     * @return bool
     */
    final public function eof()
    {
        return (! is_array($this->originalValues));
    }

    /**
     * Return the original value of a field
     * @param string $fieldName
     * @param mixed $defaultValue
     * @return mixed
     */
    final public function getOriginalValue($fieldName, $defaultValue = '')
    {
        if (! $this->eof() && array_key_exists($fieldName, $this->originalValues)) {
            return $this->originalValues[$fieldName];
        }
        return $defaultValue;
    }

    /**
     * Return an array with the original values.
     *
     * @return array
     * @throws \RuntimeException There are no original values
     */
    final public function getOriginalValues()
    {
        if ($this->eof()) {
            throw new \RuntimeException('There are no original values');
        }
        return $this->originalValues;
    }

    /**
     * Prepares the recordset to make an insertion
     * All the values are set to null
     */
    final public function addNew()
    {
        $this->originalValues = null;
        $this->values = $this->setValuesFromDatafields(null);
        $this->mode = self::RSMODE_CONNECTED_ADDNEW;
    }

    /**
     * Get the last inserted id by asking to the DBAL object.
     * This means that if an insertion happends between Update and LastInsertedID then the result
     * will not be related to the Update
     * @return int
     */
    final public function lastInsertedID()
    {
        return $this->dbal->lastInsertedID();
    }

    /**
     * Check whether the current values are different from thw original ones
     * The base are the original values
     * @return bool
     */
    final public function valuesHadChanged()
    {
        foreach ($this->originalValues as $field => $value) {
            $current = array_key_exists($field, $this->values) ? $this->values[$field] : null;
            if ($this->valueIsDifferent($value, $current)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Compare to values in order to see if they need to be updated
     * @param mixed $original
     * @param mixed $current
     * @return bool
     */
    final protected static function valueIsDifferent($original, $current)
    {
        // check if some value is null
        $originalIsNull = is_null($original);
        $currentIsNull = is_null($current);
        // both are null, there are no difference
        if ($originalIsNull and $currentIsNull) {
            return false;
        }
        // one is null, the other isn't, there is a difference
        if ($originalIsNull or $currentIsNull) {
            return true;
        }
        // do not continue using the object
        if (is_object($current)) {
            $current = strval($current);
        }
        // simple comparison
        return ($original != $current);
    }

    /**
     * @return string[]
     */
    public function getIdFields()
    {
        return $this->idFields;
    }

    /**
     * Create an array of conditions based on the current values and ids
     * This function is used on Update and on Delete
     * @param string $extraWhereClause
     * @return array
     */
    protected function sqlWhereConditions($extraWhereClause)
    {
        // get the conditions
        $conditions = [];
        if ($extraWhereClause) {
            $conditions[] = "($extraWhereClause)";
        }
        $ids = $this->getIdFields();
        if (! is_array($ids)) {
            $this->logger->warning('Recordset: cannot get the ids to locate the current the record,'
                . 'will use all the fields to create the where clause'
                . "\n"
                . print_r([
                    'entity' => $this->entity,
                    'values' => $this->values,
                ], true));
            $ids = array_keys($this->datafields);
        }
        foreach ($this->datafields as $fieldname => $field) {
            if (! array_key_exists($fieldname, $this->values)) {
                continue;
            }
            if (! in_array($fieldname, $ids)) {
                continue;
            }
            if (null === $this->originalValues[$fieldname]) {
                $conditions[] = '(' . $this->dbal->sqlIsNull($fieldname) . ')';
            } else {
                $conditions[] = '(' . $fieldname . ' = '
                    . $this->dbal->sqlQuote($this->originalValues[$fieldname], $field['commontype'], false) . ')';
            }
        }
        return $conditions;
    }

    /**
     * Create the sql statement for INSERT INTO
     * @return string
     */
    protected function sqlInsert()
    {
        $inserts = [];
        foreach ($this->datafields as $fieldname => $field) {
            $value = (array_key_exists($fieldname, $this->values)) ? $this->values[$fieldname] : null;
            $inserts[$field['name']] = $this->dbal->sqlQuote($value, $field['commontype'], true);
        }
        if (! count($inserts)) {
            throw new \LogicException('Recordset: Insert does not have any fields to insert');
        }
        return 'INSERT INTO ' . $this->entity
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
    protected function sqlUpdate($extraWhereClause)
    {
        // get the conditions to alter the current record
        $conditions = $this->sqlWhereConditions($extraWhereClause);
        // if no conditions then log error and return false
        if (! count($conditions)) {
            throw new \LogicException('Recordset: The current record does not have any conditions to update');
        }
        // get the fields that have changed compared to originalValues
        $updates = [];
        foreach ($this->datafields as $fieldname => $field) {
            if (! array_key_exists($fieldname, $this->values)) {
                $this->values[$fieldname] = null;
            }
            if ($this->valueIsDifferent($this->originalValues[$fieldname], $this->values[$fieldname])) {
                $updates[] = $fieldname . ' = '
                    . $this->dbal->sqlQuote($this->values[$fieldname], $field['commontype'], true);
            }
        }
        // if nothing to update, log error and return empty string
        if (! count($updates)) {
            return '';
        }
        // return the update statement
        return 'UPDATE ' . $this->entity
        . ' SET ' . implode(', ', $updates)
        . ' WHERE ' . implode(' AND ', $conditions)
        . ';';
    }

    /**
     * Create the sql statement for DELETE
     * @param string $extraWhereClause
     * @return string
     * @throws \LogicException
     */
    protected function sqlDelete($extraWhereClause)
    {
        // get the conditions to alter the current record
        $conditions = $this->sqlWhereConditions($extraWhereClause);
        // if no conditions then log error and return false
        if (! count($conditions)) {
            throw new \LogicException('Recordset: The current record does not have any conditions to delete');
        }
        return 'DELETE FROM ' . $this->entity
        . ' WHERE ' . implode(' AND ', $conditions)
        . ';';
    }

    /**
     * Build and execute an SQL UPDATE or INSERT sentence
     * Return how many rows where altered, if an update does not change any value then it return zero
     * Return false in case of error execution
     * @param string $extraWhereClause where clause to be append into sql on UPDATE (not insert)
     * @return bool|int
     */
    final public function update($extraWhereClause = '')
    {
        // check the current mode is on ADDNEW or EDIT
        if (self::RSMODE_CONNECTED_ADDNEW !== $this->mode and self::RSMODE_CONNECTED_EDIT !== $this->mode) {
            throw new \LogicException(
                "Recordset: The recordset is not on edit or addnew mode [current: {$this->mode}]"
            );
        }
        // check the entity is not empty
        if ('' === $this->entity) {
            throw new \LogicException('Recordset: The recordset does not have a valid unique entity');
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
            foreach ($this->originalValues as $name => $value) {
                if (! $this->valueIsDifferent($value, $this->values[$name])) {
                    continue;
                }
                $diffs[] = $name;
            }
            $this->logger->warning(print_r([
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
     * @param string $extraWhereClause
     * @return bool|int
     */
    final public function delete($extraWhereClause = '')
    {
        if (self::RSMODE_CONNECTED_EDIT !== $this->mode) {
            throw new \LogicException('Recordset: The recordset is not on edit mode [current: ' . $this->mode . ']');
        }
        // check the entity is not empty
        if ('' === $this->entity) {
            throw new \LogicException('Recordset: The recordset does not have a valid unique entity');
        }
        $sql = $this->sqlDelete($extraWhereClause);
        $altered = $this->dbal->execute($sql);
        if (0 === $altered) {
            $this->logger->warning(print_r([
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
    final public function moveNext()
    {
        return ($this->isOpen() and $this->fetchLoadValues());
    }

    /**
     * Move to the first row and read the values
     * @return bool
     */
    final public function moveFirst()
    {
        return ($this->isOpen() and $this->result->moveFirst() and $this->fetchLoadValues());
    }

    /**
     * Internal function that returns an array with the content from fields and row
     * This content is parsed according to common type to have a right type
     * meaning that booleans, dates and numbers are loaded as that and not strings
     * Anything else is loaded as string
     *
     * If the row is null or the content is null or does not exists then the value is placed as null
     *
     * @param array $row
     * @return array
     */
    private function setValuesFromDatafields($row = null)
    {
        $arr = [];
        $validrow = (! is_null($row) && is_array($row));
        foreach ($this->datafields as $fieldname => $field) {
            $variant = null;
            if ($validrow and array_key_exists($fieldname, $row) and ! is_null($row[$fieldname])) {
                $variant = $row[$fieldname];
                // these are sorted by the most common data types to avoid extra comparisons
                switch ($field['commontype']) {
                    case CommonTypes::TTEXT:
                        $variant = strval($variant);
                        break;
                    case CommonTypes::TINT:
                        $variant = intval($variant);
                        break;
                    case CommonTypes::TNUMBER:
                        $variant = floatval($variant);
                        break;
                    case CommonTypes::TBOOL:
                        $variant = (bool)$variant;
                        break;
                    case CommonTypes::TDATE:
                    case CommonTypes::TTIME:
                    case CommonTypes::TDATETIME:
                        $variant = strtotime($variant);
                        break;
                    default:
                        $variant = strval($variant);
                }
            }
            $arr[$fieldname] = $variant;
        }
        return $arr;
    }

    /**
     * Try to load values fetching a new row
     * Return true if success
     * Return false if no row was fetched, also put values to an empty array
     * @return bool
     */
    private function fetchLoadValues()
    {
        if (false !== $row = $this->result->fetchRow()) {
            $trow = $this->setValuesFromDatafields($row);
            $this->originalValues = $trow;
            $this->values = $trow;
            return true;
        }
        $this->values = [];
        $this->originalValues = null;
        return false;
    }

    /**
     * Return the number of records in the query
     * @return int
     */
    final public function getRecordCount()
    {
        return $this->result->resultCount();
    }

    /**
     * Return an associative array of fields, the key is the field name
     * and the content is an array containing name, common type and table
     * @return array|false
     */
    final public function getFields()
    {
        return (count($this->datafields)) ? $this->datafields : false;
    }

    /**
     * Return the entity name of the query
     * @return string
     */
    final public function getEntityName()
    {
        return $this->entity;
    }

    final public function count()
    {
        return $this->getRecordCount();
    }

    final public function getIterator()
    {
        return new Iterators\RecordsetIterator($this);
    }
}
