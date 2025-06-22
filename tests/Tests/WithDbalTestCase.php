<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Factory;
use EngineWorks\DBAL\Tests\DBAL\Sample\ArrayLogger;
use LogicException;
use Psr\Log\AbstractLogger;

abstract class WithDbalTestCase extends TestCase
{
    /** @var Factory|null */
    protected $factory = null;

    /** @var DBAL|null */
    protected $dbal = null;

    /** @var ArrayLogger|null */
    protected $logger = null;

    abstract protected function getFactoryNamespace(): string;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new Factory($this->getFactoryNamespace());
    }

    public function getFactory(): Factory
    {
        if ($this->factory instanceof Factory) {
            return $this->factory;
        }

        throw new LogicException('Factory has not been set.');
    }

    public function getDbal(): DBAL
    {
        if ($this->dbal instanceof DBAL) {
            return $this->dbal;
        }
        throw new LogicException('DBAL has not been set.');
    }

    public function getLogger(): ArrayLogger
    {
        if ($this->logger instanceof AbstractLogger) {
            return $this->logger;
        }
        throw new LogicException('Logger has not been set.');
    }

    /**
     * @param mixed[] $settingsArray
     * @return DBAL
     */
    protected function createDbalWithSettings(array $settingsArray = []): DBAL
    {
        $factory = $this->getFactory();
        $dbal = $factory->dbal($factory->settings($settingsArray));
        if ($dbal->isConnected()) {
            $this->fail('The DBAL should be disconnected after creation');
        }
        return $dbal;
    }

    /**
     * @param mixed[] $settingsArray
     */
    protected function setupDbalWithSettings(array $settingsArray = []): void
    {
        $logger = new ArrayLogger();
        $db = $this->createDbalWithSettings($settingsArray);
        $db->setLogger($logger);
        $this->logger = $logger;
        $this->dbal = $db;
    }
}
