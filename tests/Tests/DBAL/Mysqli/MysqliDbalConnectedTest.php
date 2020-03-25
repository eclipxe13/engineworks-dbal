<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\Mysqli;

use EngineWorks\DBAL\Tests\DBAL\TesterCases\RecordsetTester;
use EngineWorks\DBAL\Tests\DBAL\TesterCases\SqlQuoteTester;
use EngineWorks\DBAL\Tests\DBAL\TesterCases\TransactionsTester;
use EngineWorks\DBAL\Tests\DBAL\TesterTraits\DbalQueriesTrait;
use EngineWorks\DBAL\Tests\DBAL\TesterTraits\TransactionsPreventCommitTestTrait;
use EngineWorks\DBAL\Tests\DBAL\TesterTraits\TransactionsWithExceptionsTestTrait;
use EngineWorks\DBAL\Tests\MysqliWithDatabaseTestCase;

class MysqliDbalConnectedTest extends MysqliWithDatabaseTestCase
{
    // composite with transactions trait
    use TransactionsPreventCommitTestTrait;
    use TransactionsWithExceptionsTestTrait;
    use DbalQueriesTrait;

    public function testRecordsetUsingTester(): void
    {
        $tester = new RecordsetTester($this);
        $tester->execute();
    }

    public function testTransactionsUsingTester(): void
    {
        $tester = new TransactionsTester($this);
        $tester->execute();
    }

    public function testSqlQuoteUsingTester(): void
    {
        $tester = new SqlQuoteTester($this, "'\\''", "'\\\"'");
        $tester->execute();
    }

    /**
     * Override default expected behavior on trait, Mysqli knows the table names
     *
     * @see DbalQueriesTrait::overrideEntity()
     * @return string
     */
    public function overrideEntity(): string
    {
        return 'albums';
    }
}
