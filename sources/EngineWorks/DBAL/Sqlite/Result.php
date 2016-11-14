<?php
namespace EngineWorks\DBAL\Sqlite;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Result as ResultInterface;
use EngineWorks\DBAL\Traits\ResultGetFieldsCachedTrait;
use EngineWorks\DBAL\Traits\ResultImplementsCountable;
use EngineWorks\DBAL\Traits\ResultImplementsIterator;
use SQLite3Result;

/**
 * Result class implementing EngineWorks\DBAL\Result based on Sqlite3 functions
 */
class Result implements ResultInterface
{
    use ResultGetFieldsCachedTrait;
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
     */
    public function __construct(SQLite3Result $result, $numRows)
    {
        $this->query = $result;
        $this->numRows = ($numRows < 0) ? $this->obtainNumRows() : $numRows;
    }

    /**
     * Close the query and remove property association
     */
    public function __destruct()
    {
        @$this->query->finalize();
        $this->query = null;
    }

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

    public function realGetFields()
    {
        $fields = [];
        $numcolumns = $this->query->numColumns();
        for ($i = 0; $i < $numcolumns; $i++) {
            $fields[] = [
                'name' => $this->query->columnName($i),
                'commontype' => $this->getCommonType($this->query->columnType($i)),
                'table' => '',
            ];
        }
        return $fields;
    }

    /**
     * Private function to get the CommonType from the information of the field
     *
     * @param int $field
     * @return string
     */
    private function getCommonType($field)
    {
        static $types = [
            SQLITE3_INTEGER => CommonTypes::TINT,
            SQLITE3_FLOAT => CommonTypes::TNUMBER,
            SQLITE3_TEXT => CommonTypes::TTEXT,
            // static::SQLITE3_BLOB => CommonTypes::TTEXT,
            // static::SQLITE3_NULL => CommonTypes::TTEXT,
        ];
        return ($field !== false && array_key_exists($field, $types)) ? $types[$field] : CommonTypes::TTEXT;
    }

    public function getIdFields()
    {
        // TODO: investigate how to get the ID Fields from the SQLite3Result
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
