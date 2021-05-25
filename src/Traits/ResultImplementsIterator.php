<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Traits;

use EngineWorks\DBAL\Iterators\ResultIterator;
use EngineWorks\DBAL\Result;

trait ResultImplementsIterator
{
    public function getIterator(): ResultIterator
    {
        /* @var Result $this */
        return new ResultIterator($this);
    }
}
