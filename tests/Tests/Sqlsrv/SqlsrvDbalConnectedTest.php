<?php
namespace EngineWorks\DBAL\Tests\Sqlsrv;

use EngineWorks\DBAL\Tests\DbalQueriesTrait;
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
    use DbalQueriesTrait;

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
     * Override default expected behavior on trait, Sqlsrv with PDO::CURSOR_SCROLL knows the table names
     *
     * @see DbalQueriesTrait::overrideEntity()
     * @return string
     */
    public function overrideEntity(): string
    {
        return 'albums';
    }
}
