<?php namespace EngineWorks\DBAL\Tests\Sqlite;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Factory;
use EngineWorks\DBAL\Settings;

class SqliteDBALTest extends \PHPUnit_Framework_TestCase
{
    /** @var DBAL */
    private $dbal;

    /** @var Settings */
    private $settings;

    protected function setUp()
    {
        parent::setUp();
        if ($this->dbal === null) {
            $factory = new Factory('EngineWorks\DBAL\Sqlite');
            $this->settings = $factory->settings([
                'filename' => ':memory:'
            ]);
            $this->dbal = $factory->dbal($this->settings);
            $this->dbal->connect();
        }
    }


    public function testDisconnect()
    {
        $this->assertTrue($this->dbal->isConnected());
        $this->dbal->disconnect();
        $this->assertFalse($this->dbal->isConnected());
    }
    
    public function testConnect()
    {
        $this->dbal->connect();
    }
}
