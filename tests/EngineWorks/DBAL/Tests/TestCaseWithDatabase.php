<?php
namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Factory;
use EngineWorks\DBAL\Settings;
use EngineWorks\DBAL\Tests\Sample\ArrayLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

abstract class TestCaseWithDatabase extends TestCase
{
    /** @var Factory */
    protected $factory;

    /** @var DBAL */
    protected $dbal;

    /** @var Settings */
    protected $settings;

    /** @var ArrayLogger */
    protected $logger;

    abstract protected function getFactoryNamespace();

    abstract protected function getSettingsArray();

    abstract protected function createDatabaseStructure();

    abstract protected function checkIsAvailable();

    protected function setUp()
    {
        parent::setUp();
        $this->checkIsAvailable();
        if (null === $this->dbal) {
            $this->createDatabase();
        }
    }

    protected function tearDown()
    {
        parent::tearDown();
        if (null !== $this->dbal) {
            $this->dbal->disconnect();
            $this->dbal = null;
        }
    }

    private function createDatabase()
    {
        $this->logger = new ArrayLogger();
        $this->factory = new Factory($this->getFactoryNamespace());
        $this->settings = $this->factory->settings($this->getSettingsArray());
        $this->dbal = $this->factory->dbal($this->settings, $this->logger);
        if (! $this->dbal->connect()) {
            $this->fail('Cannot connect to test ' . $this->getFactoryNamespace());
        }
        $this->dbal->isConnected();
        $this->createDatabaseStructure();
        $this->createDatabaseData();

        $this->logger->clear();
    }

    public function getFixedValuesWithLabels($idFrom = 1, $idTo = 10)
    {
        $array = $this->getFixedValues($idFrom, $idTo);
        $keys = ['albumid', 'title', 'votes', 'lastview', 'isfree', 'collect'];
        $count = count($array);
        for ($i = 0; $i < $count; $i++) {
            $array[$i] = array_combine($keys, $array[$i]);
        }
        return $array;
    }

    protected function getFixedValues($idFrom = 1, $idTo = 10)
    {
        $values = [
            [1, 'Zelda Brakus III', 0, 1468513306, false, 1930.52],
            [2, 'Freddy Considine With Null', null, null, true, 383.7],
            [3, 'Deanna Schowalter', 16, 1482341572, false, 26414.34],
            [4, 'Garnett Mayer III', 13, 1452750228, true, 199.9],
            [5, 'Melba Bernier DDS', 7, 1458854926, true, 13264.42],
            [6, 'Eda Hessel', 11, 1469812984, true, 1528.9],
            [7, 'Ida Cartwright', 5, 1478943759, true, 1215.68],
            [8, 'Abdullah Cole', 19, 1466398696, false, 1566.0],
            [9, 'Felipe Lockman III', 5, 1452381289, true, 304.7],
            [10, 'Rachelle Boyer', 9, 1455741719, true, 9178.59],
        ];
        return array_slice($values, $idFrom - 1, $idTo - $idFrom + 1);
    }

    protected function convertArrayStringsToFixedValues(array $values)
    {
        $count = count($values);
        for ($i = 0; $i < $count; $i++) {
            $values[$i] = $this->convertStringsToFixedValues($values[$i]);
        }
        return $values;
    }

    protected function convertStringsToFixedValues(array $values)
    {
        return [
            'albumid' => (int) $values['albumid'],
            'title' => (string) $values['title'],
            'votes' => (is_null($values['votes'])) ? null : (int) $values['votes'],
            'lastview' => (is_null($values['lastview'])) ? null : strtotime($values['lastview']),
            'isfree' => (bool) $values['isfree'],
            'collect' => round($values['collect'], 2),
        ];
    }

    protected function convertArrayFixedValuesToStrings(array $values)
    {
        $count = count($values);
        for ($i = 0; $i < $count; $i++) {
            $values[$i] = $this->convertFixedValuesToStrings($values[$i]);
        }
        return $values;
    }

    protected function convertFixedValuesToStrings(array $values)
    {
        $values['lastview'] = date('Y-m-d H:i:s', $values['lastview']);
        $values['isfree'] = boolval($values['isfree']);
        return $values;
    }

    private function createDatabaseData()
    {
        $faker = \Faker\Factory::create();
        // create albums
        $data = $this->getFixedValues();
        for ($i = 11; $i <= 45; $i++) {
            $data[] = [
                $i,
                $faker->name,
                $faker->numberBetween(0, 20),
                $faker->numberBetween(strtotime('2016-01-01'), strtotime('2017-01-01') - 1),
                $faker->boolean,
                round($faker->numberBetween(0, 99999999) / 100, 2),
            ];
        }
        $statements = [];
        foreach ($data as $row) {
            $statements[] = 'INSERT ' . ' INTO albums (albumid, title, votes, lastview, isfree, collect) VALUES'
                . ' (' . $this->dbal->sqlQuote($row[0], DBAL::TINT)
                . ', ' . $this->dbal->sqlQuote($row[1], DBAL::TTEXT)
                . ', ' . $this->dbal->sqlQuote($row[2], DBAL::TINT)
                . ', ' . $this->dbal->sqlQuote($row[3], DBAL::TDATETIME)
                . ', ' . $this->dbal->sqlQuote($row[4], DBAL::TBOOL)
                . ', ' . $this->dbal->sqlQuote($row[5], DBAL::TNUMBER)
                . ');';
        }
        $this->executeStatements($statements);
    }

    protected function executeStatements(array $statements)
    {
        foreach ($statements as $statement) {
            $execute = $this->dbal->execute($statement);
            if (false === $execute) {
                print_r($this->logger->messages(LogLevel::ERROR));
            }
            $this->assertNotSame(false, $execute, "Fail to run $statement");
        }
    }

    protected function getDbal()
    {
        return $this->dbal;
    }
}
