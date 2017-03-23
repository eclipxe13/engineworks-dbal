<?php
namespace EngineWorks\DBAL\Sqlite;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Result as ResultInterface;
use EngineWorks\DBAL\Traits\ResultImplementsCountable;
use EngineWorks\DBAL\Traits\ResultImplementsIterator;
use SQLite3Result;

/**
 * Result class implementing EngineWorks\DBAL\Result based on Sqlite3 functions
 */
class Result implements ResultInterface
{
    use ResultImplementsCountable;
    use ResultImplementsIterator;

    /**
     * Sqlite3 element
     * @var SQLite3Result
     */
    private $query;

    /**
     * The number of the result rows
     * @var int
     */
    private $numRows;

    /**
     * Set of fieldname and commontype to use instead of detectedTypes
     * @var array
     */
    private $overrideTypes;

    /**
     * The place where getFields result is cached
     * @var array
     */
    private $cachedGetFields;

    /**
     * each call to fetchArray() returns the next result from SQLite3Result in an array,
     * until there are no more results, whereupon the next fetchArray() call will return FALSE.
     *
     * HOWEVER an additional call of fetchArray() at this point will reset back to the beginning of the result
     * set and once again return the first result. This does not seem to explicitly documented.
     *
     * http://php.net/manual/en/sqlite3result.fetcharray.php#115856
     *
     * @var bool
     */
    private $hasReachEOL = false;

    /**
     * Result based on Sqlite3
     * @param SQLite3Result $result
     * @param int $numRows If negative number then the number of rows will be obtained
     * from fetching all the rows and reset the result
     * @param array $overrideTypes
     */
    public function __construct(SQLite3Result $result, $numRows, array $overrideTypes = [])
    {
        $this->query = $result;
        $this->overrideTypes = $overrideTypes;
        $this->numRows = ($numRows < 0) ? $this->obtainNumRows() : $numRows;
    }

    /**
     * Close the query and remove property association
     */
    public function __destruct()
    {
        // suppress errors because of bug https://bugs.php.net/bug.php?id=72502
        @$this->query->finalize();
        $this->query = null;
    }

    /**
     * @param int $mode one constant value of SQLITE3 Modes
     * @return array|false
     */
    private function internalFetch($mode)
    {
        if ($this->hasReachEOL) {
            return false;
        }
        $values = $this->query->fetchArray($mode);
        if (false === $values) {
            $this->hasReachEOL = true;
        }
        return $values;
    }

    private function internalReset()
    {
        if (! $this->hasReachEOL) {
            return $this->query->reset();
        }
        $this->hasReachEOL = false;
        return true;
    }

    /**
     * Internal method to retrieve the number of rows if not supplied from constructor
     *
     * @return int
     */
    private function obtainNumRows()
    {
        $count = 0;
        if (false !== $this->internalFetch(SQLITE3_NUM)) {
            $this->getFields();
            $count = 1;
        }
        while (false !== $this->internalFetch(SQLITE3_NUM)) {
            $count = $count + 1;
        }
        $this->internalReset();
        return $count;
    }

    public function getFields()
    {
        if (null !== $this->cachedGetFields) {
            return $this->cachedGetFields;
        }
        $fields = [];
        $numcolumns = $this->query->numColumns();
        for ($i = 0; $i < $numcolumns; $i++) {
            $columnName = $this->query->columnName($i);
            $fields[] = [
                'name' => $columnName,
                'commontype' => $this->getCommonType($columnName, $this->query->columnType($i)),
                'table' => '',
            ];
        }
        $this->cachedGetFields = $fields;
        return $fields;
    }

    /**
     * Private function to get the CommonType from the information of the field
     *
     * @param string $columnName
     * @param int $field
     * @return string
     */
    private function getCommonType($columnName, $field)
    {
        static $types = [
            SQLITE3_INTEGER => CommonTypes::TINT,
            SQLITE3_FLOAT => CommonTypes::TNUMBER,
            SQLITE3_TEXT => CommonTypes::TTEXT,
            // static::SQLITE3_BLOB => CommonTypes::TTEXT,
            // static::SQLITE3_NULL => CommonTypes::TTEXT,
        ];
        if (isset($this->overrideTypes[$columnName])) {
            return $this->overrideTypes[$columnName];
        }
        if ($field !== false && array_key_exists($field, $types)) {
            return $types[$field];
        }
        return CommonTypes::TTEXT;
    }

    public function getIdFields()
    {
        return false;
    }

    public function resultCount()
    {
        return $this->numRows;
    }

    public function fetchRow()
    {
        $return = $this->internalFetch(SQLITE3_ASSOC);
        return (! is_array($return)) ? false : $return;
    }

    public function moveTo($offset)
    {
        // there are no records
        if ($this->resultCount() <= 0) {
            return false;
        }
        // the offset is out of bounds
        if ($offset < 0 || $offset > $this->resultCount() - 1) {
            return false;
        }
        // if the offset is on previous
        if (! $this->moveFirst()) {
            return false;
        }
        // move to the offset
        for ($i = 0; $i < $offset; $i++) {
            if (false === $this->internalFetch(SQLITE3_NUM)) {
                return false;
            }
        }
        return true;
    }

    public function moveFirst()
    {
        if ($this->resultCount() <= 0) {
            return false;
        }
        return $this->internalReset();
    }
}
