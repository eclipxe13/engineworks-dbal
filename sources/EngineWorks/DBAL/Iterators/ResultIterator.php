<?php
namespace EngineWorks\DBAL\Iterators;

use EngineWorks\DBAL\Result;

class ResultIterator implements \Iterator
{
    /** @var Result */
    private $result;

    /** @var int autonumeric index */
    private $index;

    /** @var array|false Store of current values */
    private $currentValues;

    /**
     * RecordsetIterator constructor.
     * @param Result $result
     */
    public function __construct(Result $result)
    {
        $this->result = $result;
    }

    public function current()
    {
        return $this->currentValues;
    }

    public function key()
    {
        return $this->index;
    }

    public function valid()
    {
        return is_array($this->currentValues);
    }

    public function next()
    {
        $this->currentValues = $this->result->fetchRow();
        $this->index = $this->index + 1;
    }

    public function rewind()
    {
        $this->result->moveFirst();
        $this->currentValues = $this->result->fetchRow();
        $this->index = 0;
    }
}
