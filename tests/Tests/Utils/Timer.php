<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\Utils;

class Timer
{
    /** @var float */
    private $start;

    public function __construct()
    {
        $this->setStart($this->current());
    }

    public function current(): float
    {
        return microtime(true);
    }

    public function getStart(): float
    {
        return $this->start;
    }

    public function setStart(float $start): void
    {
        $this->start = $start;
    }

    public function elapsed(): float
    {
        $current = $this->current();
        return $current - $this->getStart();
    }
}
