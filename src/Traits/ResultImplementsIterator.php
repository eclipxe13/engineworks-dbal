<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Traits;

use EngineWorks\DBAL\Iterators\ResultIterator;
use EngineWorks\DBAL\Result;
use Iterator;

trait ResultImplementsIterator
{
    /**
     * @return Iterator
     */
    public function getIterator()
    {
        /* @var Result $this */
        return new ResultIterator($this);
    }
}
