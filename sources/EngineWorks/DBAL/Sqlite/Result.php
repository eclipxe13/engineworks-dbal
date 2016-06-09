<?php
/*
 * DBAL Sqlite3 Result
 *
 * Author: Carlos C Soto <csoto@sia-solutions.com>
 * Licence: LGPL version 3.0
 */

namespace EngineWorks\DBAL\Sqlite;

use EngineWorks\DBAL\Result as ResultInterface;
use SQLite3Result;

class Result implements ResultInterface
{

    const SQLITE3_INTEGER = 1;
    const SQLITE3_FLOAT = 2;
    const SQLITE3_TEXT = 3;
    const SQLITE3_BLOB = 4;
    const SQLITE3_NULL = 5;

    /**
     * Resourse element
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

    public function __destruct()
    {
        $this->query->finalize();
        $this->query = null;
    }

    protected function obtainNumRows()
    {
        $count = 0;
        while (false !== $this->query->fetchArray(SQLITE3_NUM)) {
            $count = $count + 1;
        }
        $this->query->reset();
        return $count;
    }

    /**
     * Used to set a cache of getFields function
     *
     * @var array
     */
    protected $cacheGetFields = null;

    public function getFields()
    {
        if (null === $this->cacheGetFields) {
            $this->cacheGetFields = $this->realGetFields();
        }
        return $this->cacheGetFields;
    }

    /**
     * This is the implementation of realGetFields since getFields does a cache
     * inside $this->cacheGetFields
     *
     * @see self::getFields
     * @return array|false
     */
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
     * Private function to get the commontype from the information of the field
     * @param int $field
     * @return string
     */
    private function getCommonType($field)
    {
        static $types = [
            SQLITE3_INTEGER => DBAL::TINT,
            SQLITE3_FLOAT => DBAL::TNUMBER,
            SQLITE3_TEXT => DBAL::TTEXT,
            // static::SQLITE3_BLOB => DBAL::TTEXT,
            // static::SQLITE3_NULL => DBAL::TTEXT,
        ];
        $type = DBAL::TTEXT;
        if (array_key_exists($field, $types)) {
            $type = $types[$field];
        }
        return $type;
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
