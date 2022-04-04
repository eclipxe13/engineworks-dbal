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
use Psr\Log\LogLevel;

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

    public function testUpdateWhenValuesHadChangedReturnsZero(): void
    {
        // This case is exclusive of mysql

        $sql = 'SELECT * FROM albums WHERE (albumid = 1);';
        $recordset = $this->createRecordset($sql);
        $recordset->values['isfree'] = (int) $recordset->values['isfree'];
        $recordset->values['collect'] = (float) $recordset->values['collect'] + 0.00001;
        $this->assertTrue($recordset->valuesHadChanged());
        $update = $recordset->update();

        $this->assertSame(0, $update);
        $this->assertStringContainsString(
            'return zero affected rows but the values are different',
            $this->getLogger()->lastMessage(LogLevel::WARNING)
        );
    }
}
