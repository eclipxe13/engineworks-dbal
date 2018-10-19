<?php
namespace EngineWorks\DBAL\Mssql;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Result as ResultInterface;
use EngineWorks\DBAL\Traits\ResultImplementsCountable;
use EngineWorks\DBAL\Traits\ResultImplementsIterator;
use PDO;
use PDOStatement;

class Result implements ResultInterface
{
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
     * Result based on PDOStatement
     *
     * @param PDOStatement $result
     * @param int $numRows If negative number then the number of rows will be obtained
     * from fetching all the rows and move first
     * @param array $overrideTypes
     */
    public function __construct(PDOStatement $result, $numRows, array $overrideTypes = [])
    {
        $this->stmt = $result;
        $this->overrideTypes = $overrideTypes;
        $this->numRows = ($numRows < 0) ? $this->obtainNumRows() : $numRows;
    }

    /**
     * Close the query and remove property association
     */
    public function __destruct()
    {
        $this->stmt->closeCursor();
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

    public function getFields(): array
    {
        if (null !== $this->cachedGetFields) {
            return $this->cachedGetFields;
        }
        $columnsCount = $this->stmt->columnCount();
        $columns = [];
        for ($column = 0; $column < $columnsCount; $column++) {
            $columns[] = $this->stmt->getColumnMeta($column);
        }
        $fields = [];
        foreach ($columns as $fetched) {
            $fields[] = [
                'name' => $fetched['name'],
                'commontype' => $this->getCommonType($fetched['name'], $fetched['native_type']),
                'table' => $fetched['table'] ?? '',
            ];
        }
        $this->cachedGetFields = $fields;
        return $fields;
    }

    /**
     * Private function to get the common type from the information of the field
     * @param string $fieldName
     * @param string $nativeType
     * @return string
     */
    private function getCommonType($fieldName, $nativeType)
    {
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
        if (isset($this->overrideTypes[$fieldName])) {
            return $this->overrideTypes[$fieldName];
        }
        $nativeType = strtolower($nativeType);
        $type = CommonTypes::TTEXT;
        if (array_key_exists($nativeType, $types)) {
            $type = $types[$nativeType];
        }
        return $type;
    }

    public function getIdFields()
    {
        return false;
    }

    public function resultCount(): int
    {
        return $this->numRows;
    }

    public function fetchRow()
    {
        $return = $this->stmt->fetch(PDO::FETCH_ASSOC);
        return (! is_array($return)) ? false : $return;
    }

    public function moveTo(int $offset): bool
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

    public function moveFirst(): bool
    {
        if ($this->resultCount() <= 0) {
            return false;
        }
        return (false !== $this->stmt->execute());
    }
}
