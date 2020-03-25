<?php
namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Recordset;
use EngineWorks\DBAL\Result;
use Psr\Log\LogLevel;

abstract class WithDatabaseTestCase extends WithDbalTestCase
{
    abstract protected function getSettingsArray();

    abstract protected function createDatabaseStructure();

    abstract protected function checkIsAvailable();

    protected function setUp(): void
    {
        parent::setUp();
        $this->checkIsAvailable();
        if (! $this->dbal instanceof DBAL) {
            $this->setupDbalWithSettings($this->getSettingsArray());
            $this->createDatabase();
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->dbal->disconnect();
    }

    public function createRecordset(string $query, string $entity = '', array $keys = [], array $types = []): Recordset
    {
        return $this->dbal->createRecordset($query, $entity, $keys, $types);
    }

    public function queryResult(string $query, array $types = []): Result
    {
        $result = $this->dbal->queryResult($query, $types);
        if (! $result instanceof Result) {
            throw new \LogicException('Unexpected result');
        }
        return $result;
    }

    public function getFixedValuesWithLabels(int $idFrom = 1, int $idTo = 10): array
    {
        $array = $this->getFixedValues($idFrom, $idTo);
        $keys = ['albumid', 'title', 'votes', 'lastview', 'isfree', 'collect'];
        $count = count($array);
        for ($i = 0; $i < $count; $i++) {
            $array[$i] = array_combine($keys, $array[$i]);
        }
        return $array;
    }

    protected function getFixedValues(int $idFrom = 1, int $idTo = 10): array
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

    protected function convertArrayStringsToFixedValues(array $values): array
    {
        return array_map(function ($value) {
            return $this->convertStringsToFixedValues($value);
        }, $values);
    }

    protected function convertStringsToFixedValues(array $values): array
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

    protected function convertArrayFixedValuesToStrings(array $values): array
    {
        return array_map(function ($value) {
            return $this->convertFixedValuesToStrings($value);
        }, $values);
    }

    protected function convertFixedValuesToStrings(array $values): array
    {
        $values['lastview'] = date('Y-m-d H:i:s', $values['lastview']);
        $values['isfree'] = boolval($values['isfree']);
        return $values;
    }

    protected function executeStatements(array $statements)
    {
        foreach ($statements as $statement) {
            $this->executeStatement($statement);
        }
    }

    public function executeStatement(string $statement)
    {
        $execute = $this->dbal->execute($statement);
        if (false === $execute) {
            print_r($this->logger->messages(LogLevel::ERROR));
            $this->fail(get_class($this) . ' statement fail: ' . $statement);
        }
    }

    private function createDatabase()
    {
        if (! $this->dbal->connect()) {
            $this->fail(
                "Cannot connect to test {$this->getFactoryNamespace()}:\n" . implode("\n", $this->logger->allMessages())
            );
        }
        $this->dbal->isConnected();
        $this->createDatabaseStructure();
        $this->createDatabaseData();

        $this->logger->clear();
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
        $statements = ['DELETE ' . ' FROM albums;'];
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
        $this->dbal->transBegin();
        $this->executeStatements($statements);
        $this->dbal->transCommit();
    }
}
