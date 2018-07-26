<?php
namespace EngineWorks\DBAL\Tests\Mssql;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Tests\QueriesTestTrait;
use EngineWorks\DBAL\Tests\RecordsetTester;
use EngineWorks\DBAL\Tests\SqlQuoteTester;
use EngineWorks\DBAL\Tests\TestCaseWithMssqlDatabase;
use EngineWorks\DBAL\Tests\TransactionsPreventCommitTestTrait;
use EngineWorks\DBAL\Tests\TransactionsTester;
use EngineWorks\DBAL\Tests\TransactionsWithExceptionsTestTrait;

class MssqlDbalConnectedTest extends TestCaseWithMssqlDatabase
{
    // composite with transactions trait
    use TransactionsPreventCommitTestTrait;
    use TransactionsWithExceptionsTestTrait;
    use QueriesTestTrait;

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
}
