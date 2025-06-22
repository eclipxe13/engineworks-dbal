<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\Sqlsrv;

use EngineWorks\DBAL\Tests\Utils\Timer;
use EngineWorks\DBAL\Tests\WithDbalTestCase;

class SqlsrvConnectFailuresTest extends WithDbalTestCase
{
    protected function getFactoryNamespace(): string
    {
        return 'EngineWorks\DBAL\Sqlsrv';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDbalWithSettings(['connect-timeout' => 1]);
    }

    public function testConnectReturnFalseWhenCannotConnect(): void
    {
        $this->assertFalse($this->getDbal()->connect());

        $expectedLogs = [
            'info: -- Connection fail',
            'error: ',
        ];
        $actualLogs = $this->getLogger()->allMessages();
        foreach ($expectedLogs as $i => $expectedLog) {
            $this->assertStringStartsWith($expectedLog, $actualLogs[$i]);
        }
    }

    /** Test timeout configuration */
    public function testConnectToInvalidPort(): void
    {
        $expectedTimeout = 1;
        $this->setupDbalWithSettings([
            'connect-timeout' => $expectedTimeout,
            'host' => '127.0.0.1',
            'port' => 1999,
        ]);
        $timer = new Timer();
        $this->assertFalse($this->getDbal()->connect());
        $elapsed = $timer->elapsed();

        $this->assertLessThan(
            $expectedTimeout + 0.1,
            $elapsed,
            sprintf('Connect failure must take less than %.1f seconds %.1f to timeout', $expectedTimeout, $elapsed)
        );
    }
}
