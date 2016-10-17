<?php
namespace EngineWorks\DBAL\Mssql;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Result as ResultInterface;
use EngineWorks\DBAL\Traits\ResultGetFieldsCachedTrait;
use EngineWorks\DBAL\Traits\ResultImplementsCountable;
use EngineWorks\DBAL\Traits\ResultImplementsIterator;
use PDO;
use PDOStatement;

class Result implements ResultInterface
{
    use ResultGetFieldsCachedTrait;
    use ResultImplementsCountable;
    use ResultImplementsIterator;

    /**
     * PDO element
     * @var PDOStatement
     */
    private $stmt;

    /**
     * The number of the result rows
     * @var int
     */
    private $numRows;

    /**
     * Result based on PDOStatement
     *
     * @param PDOStatement $result
     * @param int $numRows If negative number then the number of rows will be obtained
     * from fetching all the rows and move first
     */
    public function __construct(PDOStatement $result, $numRows)
    {
        $this->stmt = $result;
        $this->numRows = ($numRows < 0) ? $this->obtainNumRows() : $numRows;
    }

    /**
     * Close the query and remove property association
     */
    public function __destruct()
    {
        $this->stmt->closeCursor();
        $this->stmt = null;
    }

    /**
     * Internal method to retrieve the number of rows if not supplied from constructor
     *
     * @return int
     */
    private function obtainNumRows()
    {
        $count = 0;
        while (false !== $this->stmt->fetch(PDO::FETCH_NUM)) {
            $count = $count + 1;
        }
        $this->stmt->execute();
        return $count;
    }

    protected function realGetFields()
    {
        $columnsCount = $this->stmt->columnCount();
        $columns = [];
        for ($column = 0; $column < $columnsCount; $column++) {
            $columns[] = $this->stmt->getColumnMeta($column);
        }
        $fields = [];
        foreach ($columns as $fetched) {
            $fields[] = [
                'name' => $fetched['name'],
                'commontype' => $this->getCommonType($fetched['native_type']),
                'table' => isset($fetched['table']) ? $fetched['table'] : '',
            ];
        }
        return $fields;
    }

    /**
     * Private function to get the common type from the information of the field
     * @param string $nativeType
     * @return string
     */
    private function getCommonType($nativeType)
    {
        $nativeType = strtolower($nativeType);
        static $types = [
            // integers
            'int' => CommonTypes::TINT,
            'tinyint' => CommonTypes::TINT,
            'smallint' => CommonTypes::TINT,
            'bigint' => CommonTypes::TINT,
            // floats
            'float' => CommonTypes::TNUMBER,
            'real' => CommonTypes::TNUMBER,
            'decimal' => CommonTypes::TNUMBER,
            'numeric' => CommonTypes::TNUMBER,
            'money' => CommonTypes::TNUMBER,
            'smallmoney' => CommonTypes::TNUMBER,
            // dates
            'date' => CommonTypes::TDATE,
            'time' => CommonTypes::TTIME,
            'datetime' => CommonTypes::TDATETIME,
            'smalldatetime' => CommonTypes::TDATETIME,
            // bool
            'bit' => CommonTypes::TBOOL,
            // text
            'char' => CommonTypes::TTEXT,
            'varchar' => CommonTypes::TTEXT,
            'text' => CommonTypes::TTEXT,
        ];
        $type = CommonTypes::TTEXT;
        if (array_key_exists($nativeType, $types)) {
            $type = $types[$nativeType];
        }
        return $type;
    }

    public function getIdFields()
    {
        // TODO: investigate how to get the ID Fields from the PDOStatement
        return false;
    }

    public function resultCount()
    {
        return $this->numRows;
    }

    public function fetchRow()
    {
        $return = $this->stmt->fetch(PDO::FETCH_ASSOC);
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
            if (false === $this->stmt->fetch(PDO::FETCH_NUM)) {
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
        return (false !== $this->stmt->execute());
    }
}
