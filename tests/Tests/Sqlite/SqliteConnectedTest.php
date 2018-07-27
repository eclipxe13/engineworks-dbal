<?php
namespace EngineWorks\DBAL\Tests\Sqlite;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Tests\DbalQueriesTrait;
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
     * Override default expected behavior on trait
     * it is known that sqlite does not have date, datetime, time or boolean
     *
     * @see DbalQueriesTrait::overrideTypes()
     * @return array
     */
    public function overrideTypes(): array
    {
        return [
            'lastview' => CommonTypes::TDATETIME,
            'isfree' => CommonTypes::TBOOL,
        ];
    }
}
