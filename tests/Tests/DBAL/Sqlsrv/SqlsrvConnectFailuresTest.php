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
        $this->assertFalse($this->dbal->connect());

        $expectedLogs = [
            'info: -- Connection fail',
            'error: ',
        ];
        $expectedLogsCount = count($expectedLogs);
        $actualLogs = $this->logger->allMessages();
        for ($i = 0; $i < $expectedLogsCount; $i++) {
            $this->assertStringStartsWith($expectedLogs[$i], $actualLogs[$i]);
        }
    }

    /**
     * Test timeout configuration
     *
     * @param int $expectedTimeout
     * @testWith [1]
     *           [2]
     */
    public function testConnectToInvalidPort(int $expectedTimeout): void
    {
        $this->setupDbalWithSettings(['connect-timeout' => $expectedTimeout, 'host' => '127.0.0.1', 'port' => 1999]);
        $timer = new Timer();
        $this->assertFalse($this->dbal->connect());
        $elapsed = $timer->elapsed();

        $this->assertLessThan(
            $expectedTimeout + 0.1,
            $elapsed,
            sprintf('Connect takes more than %.1f seconds %.1f to timeout', $expectedTimeout, $elapsed)
        );
        $this->assertGreaterThan(
            $expectedTimeout - 1,
            $elapsed,
            sprintf('Connect takes less than %.1f seconds %.1f to timeout', $expectedTimeout, $elapsed)
        );
    }
}
