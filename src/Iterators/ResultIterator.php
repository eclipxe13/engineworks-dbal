<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Iterators;

use EngineWorks\DBAL\Result;
use Iterator;

class ResultIterator implements Iterator
{
    /** @var Result */
    private $result;

    /** @var int autonumeric index */
    private $index;

    /** @var array|false Store of current values */
    private $currentValues;

    /**
     * ResultIterator constructor.
     * @param Result $result
     */
    public function __construct(Result $result)
    {
        $this->result = $result;
    }

    /** @return array|false */
    public function current()
    {
        return $this->currentValues;
    }

    public function key(): int
    {
        return $this->index;
    }

    public function valid(): bool
    {
        return is_array($this->currentValues);
    }

    /** @return void */
    public function next(): void
    {
        $this->currentValues = $this->result->fetchRow();
        $this->index = $this->index + 1;
    }

    /** @return void */
    public function rewind(): void
    {
        $this->result->moveFirst();
        $this->currentValues = $this->result->fetchRow();
        $this->index = 0;
    }
}
