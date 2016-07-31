<?php namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Factory;
use EngineWorks\DBAL\Settings;
use EngineWorks\DBAL\Tests\Sample\ArrayLogger;

abstract class TestCaseWithSqliteDatabase extends \PHPUnit_Framework_TestCase
{
    /** @var Factory */
    protected $factory;

    /** @var DBAL */
    protected $dbal;

    /** @var Settings */
    protected $settings;

    /** @var ArrayLogger */
    protected $logger;

    protected function setUp()
    {
        parent::setUp();
        if (null === $this->dbal) {
            $this->createSqliteDbal();
        }
    }

    private function createSqliteDbal()
    {
        $this->logger = new ArrayLogger();
        $this->factory = new Factory('EngineWorks\DBAL\Sqlite');
        $this->settings = $this->factory->settings([
            'filename' => ':memory:'
        ]);
        $this->dbal = $this->factory->dbal($this->settings);
        $this->dbal->connect();

        $this->populate();

        $this->dbal->setLogger($this->logger);
    }

    private function populate()
    {
        $faker = \Faker\Factory::create();
        // create albums
        $statements = [
            'CREATE TABLE albums (albumid INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title NVARCHAR(160)  NOT NULL);',
        ];
        for ($i = 0; $i < 45; $i++) {
            $statements[] = 'INSERT INTO albums'
                .' VALUES (NULL, ' . $this->dbal->sqlQuote($faker->name, DBAL::TTEXT) . ');';
        }
        foreach ($statements as $statement) {
            $this->assertNotSame(false, $this->dbal->execute($statement), "Fail to run $statement");
        }
    }
}
