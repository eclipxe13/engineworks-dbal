<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace EngineWorks\DBAL\Mysqli;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Result as ResultInterface;
use EngineWorks\DBAL\Traits\ResultImplementsCountable;
use EngineWorks\DBAL\Traits\ResultImplementsIterator;
use mysqli_result;

class Result implements ResultInterface
{
    use ResultImplementsCountable;
    use ResultImplementsIterator;

    private const TYPES = [
        // MYSQLI_TYPE_BIT => CommonTypes::T,
        MYSQLI_TYPE_BLOB => CommonTypes::TTEXT,
        // MYSQLI_TYPE_CHAR => CommonTypes::TINT, // MYSQLI_TYPE_TINY is the same as MYSQLI_TYPE_CHAR
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
    ];

    /**
     * Mysqli element
     * @var mysqli_result<mixed>
     */
    private $query;

    /**
     * The place where getFields result is cached
     * @var array<int, array{name: string, table: string, commontype: string, flags: int}>|null
     */
    private $cachedGetFields;

    /**
     * Set of fieldname and commontype to use instead of detectedTypes
     * @var array<string, string>
     */
    private $overrideTypes;

    /**
     * Result based on Mysqli
     *
     * @param mysqli_result<mixed> $result
     * @param array<string, string> $overrideTypes
     */
    public function __construct(mysqli_result $result, array $overrideTypes = [])
    {
        $this->query = $result;
        $this->overrideTypes = $overrideTypes;
    }

    /**
     * Close the query and remove property association
     */
    public function __destruct()
    {
        $this->query->free();
    }

    /**
     * @inheritDoc
     * @return array<int, array{name: string, table: string, commontype: string, flags: int}>
     */
    public function getFields(): array
    {
        if (null === $this->cachedGetFields) {
            $this->cachedGetFields = $this->obtainFields();
        }

        return $this->cachedGetFields;
    }

    /** @return array<int, array{name: string, table: string, commontype: string, flags: int}> */
    private function obtainFields(): array
    {
        $fields = [];
        $fetchedFields = $this->query->fetch_fields() ?: [];
        foreach ($fetchedFields as $fetched) {
            $commonType = $this->getCommonType(
                $fetched->{'name'},
                $fetched->{'type'},
                $fetched->{'length'},
                $fetched->{'decimals'}
            );
            $fields[] = [
                'name' => $fetched->name,
                'commontype' => $commonType,
                'table' => $fetched->table,
                'flags' => $fetched->flags,  // extra: used for getting the ids in the query
            ];
        }
        return $fields;
    }

    private function getCommonType(string $fieldname, int $fieldtype, int $fieldlength, int $fielddecimals): string
    {
        if (isset($this->overrideTypes[$fieldname])) {
            return $this->overrideTypes[$fieldname];
        }
        if (isset(self::TYPES[$fieldtype])) {
            $type = self::TYPES[$fieldtype];
            if (1 === $fieldlength && (CommonTypes::TINT === $type || CommonTypes::TNUMBER === $type)) {
                $type = CommonTypes::TBOOL;
            } elseif (CommonTypes::TNUMBER === $type && 0 === $fielddecimals) {
                $type = CommonTypes::TINT;
            }
        } else {
            $type = CommonTypes::TTEXT;
        }
        return $type;
    }

    public function getIdFields()
    {
        $fieldsAutoIncrement = [];
        $fieldsPrimaryKeys = [];
        $fieldsUniqueKeys = [];
        foreach ($this->getFields() as $field) {
            $flags = (int) $field['flags'];
            if (MYSQLI_AUTO_INCREMENT_FLAG & $flags) {
                $fieldsAutoIncrement[] = (string) $field['name'];
                break;
            } elseif (MYSQLI_PRI_KEY_FLAG & $flags) {
                $fieldsPrimaryKeys[] = (string) $field['name'];
            } elseif (MYSQLI_UNIQUE_KEY_FLAG & $flags) {
                $fieldsUniqueKeys[] = (string) $field['name'];
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
        return false;
    }

    public function resultCount(): int
    {
        $count = (int) $this->query->num_rows;
        return max(0, $count);
    }

    public function fetchRow()
    {
        $return = $this->query->fetch_assoc();
        return (! is_array($return)) ? false : $return;
    }

    public function moveTo(int $offset): bool
    {
        if ($offset < 0) {
            return false;
        }
        return $this->query->data_seek($offset);
    }

    public function moveFirst(): bool
    {
        if ($this->resultCount() <= 0) {
            return false;
        }
        return $this->moveTo(0);
    }
}
