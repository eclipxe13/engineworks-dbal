<?php
namespace EngineWorks\DBAL\Tests\Sqlsrv;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Result;
use EngineWorks\DBAL\Tests\DbalQueriesTester;
use EngineWorks\DBAL\Tests\RecordsetTester;
use EngineWorks\DBAL\Tests\SqlQuoteTester;
use EngineWorks\DBAL\Tests\TestCaseWithSqlsrvDatabase;
use EngineWorks\DBAL\Tests\TransactionsPreventCommitTestTrait;
use EngineWorks\DBAL\Tests\TransactionsTester;
use EngineWorks\DBAL\Tests\TransactionsWithExceptionsTestTrait;

class SqlsrvDbalConnectedTest extends TestCaseWithSqlsrvDatabase
{
    // composite with transactions trait
    use TransactionsPreventCommitTestTrait;
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
            ['name' => 'albumid', 'commontype' => CommonTypes::TINT, 'table' => ''],
            ['name' => 'title', 'commontype' => CommonTypes::TTEXT, 'table' => ''],
            ['name' => 'votes', 'commontype' => CommonTypes::TINT, 'table' => ''],
            ['name' => 'lastview', 'commontype' => CommonTypes::TDATETIME, 'table' => ''],
            ['name' => 'isfree', 'commontype' => CommonTypes::TBOOL, 'table' => ''],
            ['name' => 'collect', 'commontype' => CommonTypes::TNUMBER, 'table' => ''],
        ];
        $this->assertEquals($expectedFields, $result->getFields());
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

    public function testSqlQuoteUsingTester()
    {
        $tester = new SqlQuoteTester($this, $this->dbal);
        $tester->execute();
    }

    public function testQueriesUsingTester()
    {
        $tester = new DbalQueriesTester($this->dbal);
        $tester->execute();
    }
}
