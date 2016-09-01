<?php
namespace EngineWorks\DBAL\Traits;

use EngineWorks\DBAL\Iterators\ResultIterator;

trait ResultImplementsIterator
{
    public function getIterator()
    {
        /* @var $this \EngineWorks\DBAL\Result */
        return new ResultIterator($this);
    }
}
