<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Iterators;

use EngineWorks\DBAL\Recordset;
use Iterator;

/**
 * @implements Iterator<int|string, array<string, mixed>>
 */
class RecordsetIterator implements Iterator
{
    /** @var Recordset */
    private $recordset;

    /** @var int autonumeric index */
    private $index;

    /** @var string[] list of fields that will be used to represent the key */
    private $keyFields;

    /** @var string */
    private $keySeparator;

    /**
     * RecordsetIterator constructor.
     * @param Recordset $recordset
     * @param string[] $keyFields
     * @param string $keySeparator
     */
    public function __construct(Recordset $recordset, array $keyFields = [], string $keySeparator = '_')
    {
        $this->recordset = $recordset;
        $this->index = 0;
        $this->keyFields = $keyFields;
        $this->keySeparator = $keySeparator;
    }

    /**
     * @return array<string, mixed>
     */
    public function current(): array
    {
        return $this->recordset->values;
    }

    public function next(): void
    {
        $this->recordset->moveNext();
        $this->index = $this->index + 1;
    }

    /**
     * The key is the numeric index if no key fields where set,
     * if key index where set then the key is the original values of those keys concatenated
     *
     * @return int|string
     */
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

    public function rewind(): void
    {
        if (0 !== $this->index) {
            $this->recordset->moveFirst();
            $this->index = 0;
        }
    }
}
