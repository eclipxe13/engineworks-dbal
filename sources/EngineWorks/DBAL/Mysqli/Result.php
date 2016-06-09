<?php namespace EngineWorks\DBAL\Mysqli;

use EngineWorks\DBAL\Result as ResultInterface;
use mysqli_result;

class Result implements ResultInterface
{

    /**
     * Resourse element
     * @var mysqli_result
     */
    private $query = false;

    /**
     * Result based on Mysqli
     * @param mysqli_result $result
     */
    public function __construct(mysqli_result $result)
    {
        $this->query = $result;
    }

    public function __destruct()
    {
        $this->query->close();
        $this->query = null;
    }

    /**
     * Used to set a cache of getFields function
     * @var array
     */
    protected $cacheGetFields = null;

    /**
     * @inheritdoc
     */
    public function getFields()
    {
        if (null === $this->cacheGetFields) {
            $this->cacheGetFields = $this->realGetFields();
        }
        return $this->cacheGetFields;
    }

    public function realGetFields()
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
            // MYSQLI_TYPE_BIT => DBAL::T,
            MYSQLI_TYPE_BLOB => DBAL::TTEXT,
            MYSQLI_TYPE_CHAR => DBAL::TTEXT,
            MYSQLI_TYPE_DATE => DBAL::TDATE,
            MYSQLI_TYPE_DATETIME => DBAL::TDATETIME,
            MYSQLI_TYPE_DECIMAL => DBAL::TNUMBER,
            MYSQLI_TYPE_DOUBLE => DBAL::TNUMBER,
            // MYSQLI_TYPE_ENUM => DBAL::T,
            MYSQLI_TYPE_FLOAT => DBAL::TNUMBER,
            // MYSQLI_TYPE_GEOMETRY => DBAL::T,
            MYSQLI_TYPE_INT24 => DBAL::TINT,
            // MYSQLI_TYPE_INTERVAL => DBAL::T,
            MYSQLI_TYPE_LONG => DBAL::TINT,
            MYSQLI_TYPE_LONGLONG => DBAL::TINT,
            MYSQLI_TYPE_LONG_BLOB => DBAL::TTEXT,
            MYSQLI_TYPE_MEDIUM_BLOB => DBAL::TTEXT,
            MYSQLI_TYPE_NEWDATE => DBAL::TDATE,
            MYSQLI_TYPE_NEWDECIMAL => DBAL::TNUMBER,
            // MYSQLI_TYPE_NULL => DBAL::T,
            // MYSQLI_TYPE_SET => DBAL::T,
            MYSQLI_TYPE_SHORT => DBAL::TINT,
            MYSQLI_TYPE_STRING => DBAL::TTEXT,
            MYSQLI_TYPE_TIME => DBAL::TTIME,
            MYSQLI_TYPE_TIMESTAMP => DBAL::TINT,
            MYSQLI_TYPE_TINY => DBAL::TINT,
            MYSQLI_TYPE_TINY_BLOB => DBAL::TTEXT,
            MYSQLI_TYPE_VAR_STRING => DBAL::TTEXT,
            MYSQLI_TYPE_YEAR => DBAL::TINT,
        );
        $type = DBAL::TTEXT;
        if (array_key_exists($field->type, $types)) {
            $type = $types[$field->type];
            if ($field->length == 1 and ($type == DBAL::TINT or $type == DBAL::TNUMBER)) {
                $type = DBAL::TBOOL;
            } elseif ($type == DBAL::TNUMBER and $field->decimals == 0) {
                $type = DBAL::TINT;
            }
        }
        return $type;
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
    public function resultCount()
    {
        return $this->query->num_rows;
    }

    /**
     * @inheritdoc
     */
    public function fetchRow()
    {
        $return = $this->query->fetch_assoc();
        return (!is_array($return)) ? false : $return;
    }

    /**
     * @inheritdoc
     */
    public function moveTo($offset)
    {
        return $this->query->data_seek($offset);
    }

    /**
     * @inheritdoc
     */
    public function moveFirst()
    {
        if ($this->resultCount() <= 0) {
            return false;
        }
        return $this->moveTo(0);
    }
}
