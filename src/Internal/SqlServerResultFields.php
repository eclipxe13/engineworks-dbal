<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Internal;

use EngineWorks\DBAL\CommonTypes;
use PDOStatement;

final class SqlServerResultFields
{
    private const TYPES = [
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

    /** @var array<string, string> */
    private $overrideTypes;

    /** @var string */
    private $nativeTypeKey;

    /** @param array<string, string> $overrideTypes */
    public function __construct(array $overrideTypes, string $nativeTypeKey)
    {
        $this->overrideTypes = $overrideTypes;
        $this->nativeTypeKey = $nativeTypeKey;
    }

    /** @return array<int, array{name: string, table: string, commontype: string}> */
    public function obtainFields(PDOStatement $statement): array
    {
        $columnsCount = $statement->columnCount();
        $columns = [];
        for ($column = 0; $column < $columnsCount; $column++) {
            $columnMeta = $statement->getColumnMeta($column);
            if (is_array($columnMeta)) {
                $columns[] = $columnMeta;
            }
        }
        $fields = [];
        foreach ($columns as $fetched) {
            $fields[] = [
                'name' => $fetched['name'],
                'commontype' => $this->getCommonType(
                    $fetched['name'],
                    is_scalar($fetched[$this->nativeTypeKey]) ? (string) $fetched[$this->nativeTypeKey] : ''
                ),
                'table' => $fetched['table'] ?? '',
            ];
        }
        return $fields;
    }

    /**
     * Private function to get the common type from the information of the field
     */
    private function getCommonType(string $fieldName, string $nativeType): string
    {
        if (isset($this->overrideTypes[$fieldName])) {
            return $this->overrideTypes[$fieldName];
        }
        $nativeType = strtolower($nativeType);
        return self::TYPES[$nativeType] ?? CommonTypes::TTEXT;
    }
}
