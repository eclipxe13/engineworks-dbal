<?php
namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Result;
use EngineWorks\DBAL\Tests\Sample\ArrayLogger;

/* @var $this \EngineWorks\DBAL\Tests\TestCaseWithDatabase */

trait QueriesTestTrait
{
    /** @return DBAL */
    abstract protected function getDbal();

    abstract protected function getLogger(): ArrayLogger;

    public function testDisconnectAndReconnect()
    {
        // $dbal is already connected
        $dbal = $this->getDbal();

        $this->assertTrue($dbal->isConnected());

        $dbal->disconnect();
        $this->assertFalse($dbal->isConnected());

        $this->assertTrue($dbal->connect());
        $this->assertTrue($dbal->isConnected());

        $logger = $this->getLogger();
        $this->assertSame($logger->messages('info', true), $logger->allMessages(), 'All messages should be level info');
    }

    public function testQueryOneWithValues()
    {
        $expected = 45;
        $value = $this->getDbal()->queryOne('SELECT COUNT(*) FROM albums;');

        $this->assertEquals($expected, $value);
    }

    public function testQueryOneWithDefault()
    {
        $expected = -10;
        $value = $this->getDbal()->queryOne('SELECT 1 FROM albums WHERE (albumid = -1);', $expected);

        $this->assertSame($expected, $value);
    }

    public function testQueryRow()
    {
        $expectedRows = $this->convertArrayFixedValuesToStrings($this->getFixedValuesWithLabels(5, 5));

        $sql = 'SELECT * FROM albums WHERE (albumid = 5);';
        $result = $this->getDbal()->queryRow($sql);
        $this->assertInternalType('array', $result);
        $this->assertEquals($expectedRows, [$result]);
    }

    public function testQueryArray()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid BETWEEN 1 AND 5);';
        $result = $this->getDbal()->queryArray($sql);
        $this->assertInternalType('array', $result);
        $this->assertCount(5, $result);

        $expectedRows = $this->getFixedValuesWithLabels(1, 5);
        $result = $this->convertArrayStringsToFixedValues($result);
        $this->assertEquals($expectedRows, $result);
    }

    public function testExecuteWithError()
    {
        $expectedMessage = 'Invalid SQL Statement';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->getDbal()->execute('BAD STATEMENT;', $expectedMessage);
    }

    public function queryResultTestOverrideTypes(): array
    {
        // it is known that sqlite does not have date, datetime, time or boolean
        return [];
    }

    public function queryResultTestExpectedTableName(): string
    {
        return '';
    }

    public function testQueryResult()
    {
        $expectedTablename = $this->queryResultTestExpectedTableName();
        $sql = 'SELECT * FROM albums WHERE (albumid = 5);';
        /* @var \EngineWorks\DBAL\Result $result */
        $result = $this->getDbal()->queryResult($sql, $this->queryResultTestOverrideTypes());
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(1, $result->resultCount());
        // get first
        $fetchedFirst = $result->fetchRow();
        $this->assertInternalType('array', $fetchedFirst);
        // move and get first again
        $this->assertTrue($result->moveFirst());
        $fetchedSecond = $result->fetchRow();
        // test they are the same
        $this->assertEquals($fetchedFirst, $fetchedSecond);

        $expectedFields = [
            ['name' => 'albumid', 'commontype' => CommonTypes::TINT, 'table' => $expectedTablename],
            ['name' => 'title', 'commontype' => CommonTypes::TTEXT, 'table' => $expectedTablename],
            ['name' => 'votes', 'commontype' => CommonTypes::TINT, 'table' => $expectedTablename],
            ['name' => 'lastview', 'commontype' => CommonTypes::TDATETIME, 'table' => $expectedTablename],
            ['name' => 'isfree', 'commontype' => CommonTypes::TBOOL, 'table' => $expectedTablename],
            ['name' => 'collect', 'commontype' => CommonTypes::TNUMBER, 'table' => $expectedTablename],
        ];

        $this->assertArraySubset($expectedFields, $result->getFields());
    }
}
