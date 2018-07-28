<?php
namespace EngineWorks\DBAL\Traits;

trait ResultImplementsCountable
{
    public function count(): int
    {
        return $this->resultCount();
    }
}
