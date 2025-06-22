<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\TesterTraits;

use EngineWorks\DBAL\Tests\DBAL\Sample\ArrayLogger;
use Psr\Log\NullLogger;

trait DbalLoggerTrait
{
    public function testLoggerProperty(): void
    {
        $dbal = $this->createDbalWithSettings([]);
        $this->assertInstanceOf(NullLogger::class, $dbal->getLogger());
        $logger = new ArrayLogger();
        $dbal->setLogger($logger);
        $this->assertSame($logger, $dbal->getLogger());
    }
}
