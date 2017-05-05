<?php
namespace EngineWorks\DBAL\Tests\Mysqli;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Result;
use EngineWorks\DBAL\Tests\RecordsetTester;
use EngineWorks\DBAL\Tests\TestCaseWithMysqliDatabase;
use EngineWorks\DBAL\Tests\TransactionsTester;
use EngineWorks\DBAL\Tests\TransactionsWithExceptionsTestTrait;

class MysqliDbalConnectedTest extends TestCaseWithMysqliDatabase
{
    // composite with transactions trait
    use TransactionsWithExceptionsTestTrait;

    public function testConnectAndDisconnect()
    {
        $this->dbal->disconnect();

        // connect, this is actually reconnect since TestCaseWithDatabase class fail if cannot connect
        $this->logger->clear();
        $this->assertTrue($this->dbal->connect());
        $expectedLogs = [
            'info: -- Connect and database select OK',
        ];
        $this->assertEquals($expectedLogs, $this->logger->allMessages());

        // disconnect
        $this->logger->clear();
        $this->dbal->disconnect();
        $this->assertFalse($this->dbal->isConnected());
        $expectedLogs = [
            'info: -- Disconnection',
        ];
        $this->assertEquals($expectedLogs, $this->logger->allMessages());
    }

    public function testQuoteMultibyte()
    {
        $text = 'á é í ó ú';
        $sql = 'SELECT ' . $this->dbal->sqlQuote($text, CommonTypes::TTEXT);
        $this->assertSame("SELECT '$text'", $sql);
        $this->assertSame($text, $this->dbal->queryOne($sql));
    }

    public function testQueryOneWithValues()
    {
        $expected = 45;
        $value = $this->dbal->queryOne('SELECT COUNT(*) FROM albums;');

        $this->assertEquals($expected, $value);
    }

    public function testQueryOneWithError()
    {
        $expected = -10;
        $value = $this->dbal->queryOne('SELECT albumid FROM albums WHERE (albumid = -1);', $expected);

        $this->assertSame($expected, $value);
    }

    public function testQueryRow()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid = 5);';
        $result = $this->dbal->queryRow($sql);
        $this->assertInternalType('array', $result);

        $expectedRows = $this->convertArrayFixedValuesToStrings($this->getFixedValuesWithLabels(5, 5));
        $this->assertEquals($expectedRows, [$result]);
    }

    public function testQueryArray()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid BETWEEN 1 AND 5);';
        $result = $this->dbal->queryArray($sql);
        $this->assertInternalType('array', $result);
        $this->assertCount(5, $result);

        $expectedRows = $this->getFixedValuesWithLabels(1, 5);
        $result = $this->convertArrayStringsToFixedValues($result);
        $this->assertEquals($expectedRows, $result);
    }

    public function testQueryResult()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid = 5);';
        $result = $this->dbal->queryResult($sql);
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
            ['name' => 'albumid', 'commontype' => CommonTypes::TINT, 'table' => 'albums'],
            ['name' => 'title', 'commontype' => CommonTypes::TTEXT, 'table' => 'albums'],
            ['name' => 'votes', 'commontype' => CommonTypes::TINT, 'table' => 'albums'],
            ['name' => 'lastview', 'commontype' => CommonTypes::TDATETIME, 'table' => 'albums'],
            ['name' => 'isfree', 'commontype' => CommonTypes::TBOOL, 'table' => 'albums'],
            ['name' => 'collect', 'commontype' => CommonTypes::TNUMBER, 'table' => 'albums'],
        ];
        $actualFields = [];
        $retrievedFields = $result->getFields();
        foreach ($retrievedFields as $retrievedField) {
            $actualFields[] = [
                'name' => $retrievedField['name'],
                'commontype' => $retrievedField['commontype'],
                'table' => $retrievedField['table'],
            ];
        }
        $this->assertEquals($expectedFields, $actualFields);
    }

    public function testExecuteWithError()
    {
        $expectedMessage = 'Invalid SQL Statement';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->dbal->execute('BAD STATEMENT;', $expectedMessage);
    }

    public function testRecordsetUsingTester()
    {
        $tester = new RecordsetTester($this, $this->dbal);
        $tester->execute();
    }

    public function testTransactionsUsingTester()
    {
        $tester = new TransactionsTester($this, $this->dbal);
        $tester->execute();
    }
}
