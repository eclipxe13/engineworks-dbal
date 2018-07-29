<?php
namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Factory;
use EngineWorks\DBAL\Tests\DBAL\Sample\ArrayLogger;
use PHPUnit\Framework\TestCase;

abstract class WithDbalTestCase extends TestCase
{
    /** @var Factory */
    protected $factory;

    /** @var DBAL */
    protected $dbal;

    /** @var ArrayLogger */
    protected $logger;

    abstract protected function getFactoryNamespace();

    protected function setUp()
    {
        parent::setUp();
        $this->factory = new Factory($this->getFactoryNamespace());
    }

    public function getFactory(): Factory
    {
        return $this->factory;
    }

    public function getDbal(): DBAL
    {
        return $this->dbal;
    }

    public function getLogger(): ArrayLogger
    {
        return $this->logger;
    }

    protected function createDbalWithSettings(array $settingsArray = []): DBAL
    {
        $dbal = $this->factory->dbal($this->factory->settings($settingsArray));
        if ($dbal->isConnected()) {
            $this->fail('The DBAL should be disconnected after creation');
        }
        return $dbal;
    }

    /** @return void */
    protected function setupDbalWithSettings(array $settingsArray = [])
    {
        $this->dbal = $this->createDbalWithSettings($settingsArray);
        $this->logger = new ArrayLogger();
        $this->dbal->setLogger($this->logger);
    }
}
