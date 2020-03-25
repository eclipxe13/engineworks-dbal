<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Iterators;

use EngineWorks\DBAL\Recordset;
use Iterator;

class RecordsetIterator implements Iterator
{
    /** @var Recordset */
    private $recordset;

    /** @var int autonumeric index */
    private $index;

    /** @var array list of fields that will be used to represent the key */
    private $keyFields;

    /** @var string */
    private $keySeparator;

    /**
     * RecordsetIterator constructor.
     * @param Recordset $recordset
     * @param array $keyFields
     * @param string $keySeparator
     */
    public function __construct(Recordset $recordset, array $keyFields = [], $keySeparator = '_')
    {
        $this->recordset = $recordset;
        $this->index = 0;
        $this->keyFields = $keyFields;
        $this->keySeparator = $keySeparator;
    }

    public function current(): array
    {
        return $this->recordset->values;
    }

    /** @return void */
    public function next(): void
    {
        $this->recordset->moveNext();
        $this->index = $this->index + 1;
    }

    public function key()
    {
        if (! count($this->keyFields)) {
            return $this->index;
        }
        $key = [];
        foreach ($this->keyFields as $fieldName) {
            $key[$fieldName] = $this->recordset->getOriginalValue($fieldName);
        }
        return implode($this->keySeparator, $key);
    }

    public function valid(): bool
    {
        return ! $this->recordset->eof();
    }

    /** @return void */
    public function rewind(): void
    {
        if (0 !== $this->index) {
            $this->recordset->moveFirst();
            $this->index = 0;
        }
    }
}
