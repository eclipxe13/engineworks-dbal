<?php

namespace EngineWorks\DBAL\Traits;

use EngineWorks\DBAL\Iterators\ResultIterator;

trait ResultImplementsIterator
{
    public function getIterator()
    {
        return new ResultIterator($this);
    }
}
