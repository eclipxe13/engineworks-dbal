<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Traits;

trait ResultImplementsCountable
{
    public function count(): int
    {
        return $this->resultCount();
    }
}
