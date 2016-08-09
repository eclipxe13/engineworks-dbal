<?php

namespace EngineWorks\DBAL\Traits;

trait ResultImplementsCountable
{
    public function count()
    {
        return $this->resultCount();
    }
}
