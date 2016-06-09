<?php namespace EngineWorks\DBAL\Mysqli;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Result as ResultInterface;
use EngineWorks\DBAL\Traits\SettingsCachedGetFieldsTrait;
use mysqli_result;

class Result implements ResultInterface
{
    
    use SettingsCachedGetFieldsTrait;

    /**
     * Resourse element
     * @var mysqli_result
     */
    private $query = false;

    /**
     * Result based on Mysqli
     *
     * @param mysqli_result $result
     */
    public function __construct(mysqli_result $result)
    {
        $this->query = $result;
    }

    /**
     * Close the query and remove property association
     */
    public function __destruct()
    {
        $this->query->close();
        $this->query = null;
    }

    protected function realGetFields()
    {
        $fields = [];
        foreach ($this->query->fetch_fields() as $fetched) {
            $fields[] = [
                "name" => $fetched->name,
                "commontype" => $this->getCommonType($fetched),
                "table" => $fetched->table,
                "flags" => $fetched->flags,  // extra: used for getting the ids in the query
            ];
        }
        return $fields;
    }

    /**
     * Private function to get the commontype from the information of the field
     * @param object $field
     * @return string
     */
    private function getCommonType($field)
    {
        static $types = array(
            // MYSQLI_TYPE_BIT => CommonTypes::T,
            MYSQLI_TYPE_BLOB => CommonTypes::TTEXT,
            MYSQLI_TYPE_CHAR => CommonTypes::TTEXT,
            MYSQLI_TYPE_DATE => CommonTypes::TDATE,
            MYSQLI_TYPE_DATETIME => CommonTypes::TDATETIME,
            MYSQLI_TYPE_DECIMAL => CommonTypes::TNUMBER,
            MYSQLI_TYPE_DOUBLE => CommonTypes::TNUMBER,
            // MYSQLI_TYPE_ENUM => CommonTypes::T,
            MYSQLI_TYPE_FLOAT => CommonTypes::TNUMBER,
            // MYSQLI_TYPE_GEOMETRY => CommonTypes::T,
            MYSQLI_TYPE_INT24 => CommonTypes::TINT,
            // MYSQLI_TYPE_INTERVAL => CommonTypes::T,
            MYSQLI_TYPE_LONG => CommonTypes::TINT,
            MYSQLI_TYPE_LONGLONG => CommonTypes::TINT,
            MYSQLI_TYPE_LONG_BLOB => CommonTypes::TTEXT,
            MYSQLI_TYPE_MEDIUM_BLOB => CommonTypes::TTEXT,
            MYSQLI_TYPE_NEWDATE => CommonTypes::TDATE,
            MYSQLI_TYPE_NEWDECIMAL => CommonTypes::TNUMBER,
            // MYSQLI_TYPE_NULL => CommonTypes::T,
            // MYSQLI_TYPE_SET => CommonTypes::T,
            MYSQLI_TYPE_SHORT => CommonTypes::TINT,
            MYSQLI_TYPE_STRING => CommonTypes::TTEXT,
            MYSQLI_TYPE_TIME => CommonTypes::TTIME,
            MYSQLI_TYPE_TIMESTAMP => CommonTypes::TINT,
            MYSQLI_TYPE_TINY => CommonTypes::TINT,
            MYSQLI_TYPE_TINY_BLOB => CommonTypes::TTEXT,
            MYSQLI_TYPE_VAR_STRING => CommonTypes::TTEXT,
            MYSQLI_TYPE_YEAR => CommonTypes::TINT,
        );
        $type = CommonTypes::TTEXT;
        if (array_key_exists($field->type, $types)) {
            $type = $types[$field->type];
            if ($field->length == 1 and ($type == CommonTypes::TINT or $type == CommonTypes::TNUMBER)) {
                $type = CommonTypes::TBOOL;
            } elseif ($type == CommonTypes::TNUMBER and $field->decimals == 0) {
                $type = CommonTypes::TINT;
            }
        }
        return $type;
    }

    public function getIdFields()
    {
        $return = false;
        $fieldsAutoIncrement = [];
        $fieldsPrimaryKeys = [];
        $fieldsUniqueKeys = [];
        foreach ($this->getFields() as $field) {
            $flags = $field["flags"];
            if (MYSQLI_AUTO_INCREMENT_FLAG & $flags) {
                $fieldsAutoIncrement[] = $field["name"];
                break;
            } elseif (MYSQLI_PRI_KEY_FLAG & $flags) {
                $fieldsPrimaryKeys[] = $field["name"];
            } elseif (MYSQLI_UNIQUE_KEY_FLAG & $flags) {
                $fieldsUniqueKeys[] = $field["name"];
            }
        }
        if (count($fieldsAutoIncrement)) {
            return $fieldsAutoIncrement;
        }
        if (count($fieldsPrimaryKeys)) {
            return $fieldsPrimaryKeys;
        }
        if (count($fieldsUniqueKeys)) {
            return $fieldsUniqueKeys;
        }
        return $return;
    }

    public function resultCount()
    {
        return $this->query->num_rows;
    }

    public function fetchRow()
    {
        $return = $this->query->fetch_assoc();
        return (!is_array($return)) ? false : $return;
    }

    public function moveTo($offset)
    {
        return $this->query->data_seek($offset);
    }

    public function moveFirst()
    {
        if ($this->resultCount() <= 0) {
            return false;
        }
        return $this->moveTo(0);
    }
}
