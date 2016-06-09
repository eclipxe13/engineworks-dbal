<?php namespace EngineWorks\DBAL\Sqlite;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Result as ResultInterface;
use EngineWorks\DBAL\Traits\SettingsCachedGetFieldsTrait;
use SQLite3Result;

class Result implements ResultInterface
{
    use SettingsCachedGetFieldsTrait;

    /**
     * Sqlite3 element
     * @var SQLite3Result
     */
    private $query = false;

    /**
     * The number of the result rows
     * @var int
     */
    private $numRows;

    /**
     * Result based on Sqlite3
     * @param SQLite3Result $result
     * @param int $numRows If negative number then the number of rows will be obtained
     * from fetching all the rows and reset the result
     */
    public function __construct(SQLite3Result $result, $numRows)
    {
        $this->query = $result;
        if ($numRows < 0) {
            $numRows = $this->obtainNumRows();
        }
        $this->numRows = $numRows;
    }

    /**
     * Close the query and remove property association
     */
    public function __destruct()
    {
        $this->query->finalize();
        $this->query = null;
    }

    /**
     * Internal method to retrieve the number of rows if not supplied from constructor
     *
     * @return int
     */
    private function obtainNumRows()
    {
        $count = 0;
        while (false !== $this->query->fetchArray(SQLITE3_NUM)) {
            $count = $count + 1;
        }
        $this->query->reset();
        return $count;
    }

    public function realGetFields()
    {
        $fields = [];
        $numcolumns = $this->query->numColumns();
        for ($i = 0; $i < $numcolumns; $i++) {
            $fields[] = [
                "name" => $this->query->columnName($i),
                "commontype" => $this->getCommonType($this->query->columnType($i)),
                "table" => "",
                "flags" => null,  // extra: used for getting the ids in the query
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
        return (array_key_exists($field, $types)) ? $types[$field] : CommonTypes::TTEXT;
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
        $return = $this->query->fetchArray(SQLITE3_ASSOC);
        return (!is_array($return)) ? false : $return;
    }

    public function moveTo($offset)
    {
        if ($offset < 0) {
            return false;
        }
        if (!$this->numRows) {
            return false;
        }
        if ($offset > $this->numRows - 1) {
            return false;
        }
        for ($i = 0; $i < $offset; $i++) {
            if (false === $this->query->fetchArray(SQLITE3_NUM)) {
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
        return $this->query->reset();
    }
}
