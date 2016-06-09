<?php namespace EngineWorks\DBAL\Tests\Sqlite;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Factory;
use EngineWorks\DBAL\Settings;
use EngineWorks\DBAL\Tests\Sample\ArrayLogger;
use Psr\Log\NullLogger;

class SqliteDBALTest extends \PHPUnit_Framework_TestCase
{
    /** @var Factory */
    private $factory;

    /** @var DBAL */
    private $dbal;

    /** @var Settings */
    private $settings;

    protected function setUp()
    {
        parent::setUp();
        if ($this->dbal === null) {
            $this->factory = new Factory('EngineWorks\DBAL\Sqlite');
            $this->settings = $this->factory->settings([
                'filename' => ':memory:'
            ]);
            $this->dbal = $this->factory->dbal($this->settings);
            $this->dbal->connect();
        }
    }

    /** @return ArrayLogger */
    protected function dbalGetArrayLogger()
    {
        return $this->dbal->getLogger();
    }

    protected function dbalSetArrayLogger()
    {
        $this->dbal->setLogger(new ArrayLogger());
    }

    protected function dbalUnsetArrayLogger()
    {
        $this->dbal->setLogger(new NullLogger());
    }

    public function testDisconnect()
    {
        $this->assertTrue($this->dbal->isConnected());
        $this->dbalSetArrayLogger();
        $this->dbal->disconnect();
        $expectedLogs = [
            'info: -- Disconnection'
        ];
        $this->assertFalse($this->dbal->isConnected());
        $this->assertSame($expectedLogs, $this->dbalGetArrayLogger()->allMessages());
        $this->dbal->disconnect();
        $this->assertSame(
            $expectedLogs,
            $this->dbalGetArrayLogger()->allMessages(),
            'Disconnect create two logs instead of only one'
        );
    }

    public function testConnectReturnFalseWhenCannotConnect()
    {
        $dbal = $this->factory->dbal($this->factory->settings([
            'filename' => 'non-existent',
            'flags' => 0,
        ]));
        $logger = new ArrayLogger();
        $dbal->setLogger($logger);
        $this->assertFalse($dbal->connect());
        $expectedLogs = [
            'info: -- Connection fail',
            'error: Cannot create SQLite3 object: Unable to open database: out of memory'
        ];
        $this->assertSame($expectedLogs, $logger->allMessages());
    }

    public function testConnectLogger()
    {
        $this->assertTrue($this->dbal->isConnected());
        $this->dbal->disconnect();
        $this->dbalSetArrayLogger();
        $this->assertTrue($this->dbal->connect());
        $expectedLogs = [
            'info: -- Connection success'
        ];
        $this->assertSame($expectedLogs, $this->dbalGetArrayLogger()->allMessages());
    }
}
