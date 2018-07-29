<?php
namespace EngineWorks\DBAL\Tests\DBAL\Sqlsrv;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Tests\DBAL\Sample\ArrayLogger;
use EngineWorks\DBAL\Tests\Utils\Timer;
use EngineWorks\DBAL\Tests\WithDbalTestCase;

class SqlsrvConnectFailuresTest extends WithDbalTestCase
{
    /** @var ArrayLogger */
    protected $logger;

    protected function getFactoryNamespace()
    {
        return 'EngineWorks\DBAL\Sqlsrv';
    }

    protected function setUp()
    {
        parent::setUp();
        $this->setupDbalWithSettings(['connect-timeout' => 1]);
    }

    protected function createDbalWithSettings(array $settingsArray = []): DBAL
    {
        $dbal = $this->factory->dbal($this->factory->settings($settingsArray));
        if ($dbal->isConnected()) {
            $this->fail('The DBAL should be disconnected');
        }
        return $dbal;
    }

    protected function setupDbalWithSettings(array $settingsArray = [])
    {
        $this->dbal = $this->createDbalWithSettings($settingsArray);
        $this->logger = new ArrayLogger();
        $this->dbal->setLogger($this->logger);
    }

    protected function getDefaultDbalSettingsArray(): array
    {
        return [];
    }

    public function testConnectReturnFalseWhenCannotConnect()
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
    public function testConnectToInvalidPort(int $expectedTimeout)
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
