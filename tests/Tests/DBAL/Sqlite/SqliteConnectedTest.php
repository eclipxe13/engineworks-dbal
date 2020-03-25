<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\Sqlite;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Tests\DBAL\TesterCases\RecordsetTester;
use EngineWorks\DBAL\Tests\DBAL\TesterCases\SqlQuoteTester;
use EngineWorks\DBAL\Tests\DBAL\TesterCases\TransactionsTester;
use EngineWorks\DBAL\Tests\DBAL\TesterTraits\DbalQueriesTrait;
use EngineWorks\DBAL\Tests\DBAL\TesterTraits\TransactionsPreventCommitTestTrait;
use EngineWorks\DBAL\Tests\DBAL\TesterTraits\TransactionsWithExceptionsTestTrait;
use EngineWorks\DBAL\Tests\SqliteWithDatabaseTestCase;

class SqliteConnectedTest extends SqliteWithDatabaseTestCase
{
    // composite with transactions trait
    use TransactionsWithExceptionsTestTrait;
    use DbalQueriesTrait;
    use TransactionsPreventCommitTestTrait;

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
        $tester = new SqlQuoteTester($this);
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
