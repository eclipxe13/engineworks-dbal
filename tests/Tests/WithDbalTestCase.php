<?php
namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Factory;
use PHPUnit\Framework\TestCase;

abstract class WithDbalTestCase extends TestCase
{
    /** @var Factory */
    protected $factory;

    /** @var DBAL */
    protected $dbal;

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
}
