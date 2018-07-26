<?php
namespace EngineWorks\DBAL\Tests\Sqlite;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Tests\QueriesTestTrait;
use EngineWorks\DBAL\Tests\RecordsetTester;
use EngineWorks\DBAL\Tests\SqlQuoteTester;
use EngineWorks\DBAL\Tests\TestCaseWithSqliteDatabase;
use EngineWorks\DBAL\Tests\TransactionsPreventCommitTestTrait;
use EngineWorks\DBAL\Tests\TransactionsTester;
use EngineWorks\DBAL\Tests\TransactionsWithExceptionsTestTrait;

class SqliteConnectedTest extends TestCaseWithSqliteDatabase
{
    // composite with transactions trait
    use TransactionsPreventCommitTestTrait;
    use TransactionsWithExceptionsTestTrait;
    use QueriesTestTrait;

    public function testConnectAndDisconnect()
    {
        $this->dbal->disconnect();

        // connect, this is actually reconnect since TestCaseWithDatabase class fail if cannot connect
        $this->logger->clear();
        $this->assertTrue($this->dbal->connect());
        $expectedLogs = [
            'info: -- Connection success',
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

    /**
     * Override default expected behavior on trait
     * it is known that sqlite does not have date, datetime, time or boolean
     *
     * @see QueriesTestTrait::queryResultTestOverrideTypes()
     * @return array
     */
    public function queryResultTestOverrideTypes(): array
    {
        return [
            'lastview' => CommonTypes::TDATETIME,
            'isfree' => CommonTypes::TBOOL,
        ];
    }
}
