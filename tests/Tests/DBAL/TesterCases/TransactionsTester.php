<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\TesterCases;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Tests\DBAL\Sample\ArrayLogger;
use EngineWorks\DBAL\Tests\WithDatabaseTestCase;
use Psr\Log\LogLevel;

final class TransactionsTester
{
    /** @var WithDatabaseTestCase */
    private $test;

    /** @var DBAL */
    private $dbal;

    /** @var ArrayLogger */
    private $logger;

    /** @var int */
    private $count;

    public function __construct(WithDatabaseTestCase $test)
    {
        $this->test = $test;
        $this->dbal = $test->getDbal();
        $this->logger = $test->getLogger();
        $this->count = $this->getRecordCount();
    }

    public function execute(): void
    {
        $this->testTransactionLevelBeginRollbackCommit();
        $this->testTransactionRollback();
        $this->testTransactionCommit();
        $this->testNestedCommit();
        $this->testNestedRollback();
        $this->testNestedRollbackCommitRollback();
        $this->testNestedCommitRollbackCommit();
    }

    public function testTransactionLevelBeginRollbackCommit(): void
    {
        $this->test->assertSame(0, $this->dbal->getTransactionLevel());
        $this->dbal->transBegin();
        $this->test->assertStringContainsString('TRANSACTION', $this->logger->lastMessage(LogLevel::DEBUG));
        $this->test->assertSame(1, $this->dbal->getTransactionLevel());
        $this->dbal->transRollback();
        $this->test->assertStringContainsString('ROLLBACK', $this->logger->lastMessage(LogLevel::DEBUG));
        $this->test->assertSame(0, $this->dbal->getTransactionLevel());
        $this->dbal->transBegin();
        $this->test->assertSame(1, $this->dbal->getTransactionLevel());
        $this->dbal->transCommit();
        $this->test->assertStringContainsString('COMMIT', $this->logger->lastMessage(LogLevel::DEBUG));
        $this->test->assertSame(0, $this->dbal->getTransactionLevel());
    }

    public function testTransactionRollback(): void
    {
        $this->dbal->transBegin();
        $this->insertRecord(1000);
        $this->test->assertEquals($this->count + 1, $this->getRecordCount());
        $this->dbal->transRollback();
        $this->test->assertEquals($this->count, $this->getRecordCount());
    }

    public function testTransactionCommit(): void
    {
        $this->dbal->transBegin();
        $this->insertRecord(1000);
        $this->test->assertEquals($this->count + 1, $this->getRecordCount());
        $this->dbal->transCommit();
        $this->test->assertEquals($this->count + 1, $this->getRecordCount());
        $this->deleteRecord(1000);
        $this->test->assertEquals($this->count, $this->getRecordCount());
    }

    public function testNestedCommit(): void
    {
        $this->dbal->transBegin();
        $this->insertRecord(1000);
        $this->test->assertEquals($this->count + 1, $this->getRecordCount());

        $this->dbal->transBegin();
        $this->test->assertSame(2, $this->dbal->getTransactionLevel());
        $this->test->assertStringContainsString('LEVEL_1', $this->logger->lastMessage(LogLevel::DEBUG));
        $this->insertRecord(1001);
        $this->test->assertEquals($this->count + 2, $this->getRecordCount());

        $this->dbal->transBegin();
        $this->test->assertStringContainsString('LEVEL_2', $this->logger->lastMessage(LogLevel::DEBUG));
        $this->test->assertSame(3, $this->dbal->getTransactionLevel());
        $this->insertRecord(1002);
        $this->test->assertEquals($this->count + 3, $this->getRecordCount());

        $this->dbal->transCommit();
        $this->test->assertStringContainsString('LEVEL_2', $this->logger->lastMessage(LogLevel::DEBUG));
        $this->test->assertSame(2, $this->dbal->getTransactionLevel());

        $this->dbal->transCommit();
        $this->test->assertStringContainsString('LEVEL_1', $this->logger->lastMessage(LogLevel::DEBUG));
        $this->test->assertSame(1, $this->dbal->getTransactionLevel());

        $this->dbal->transCommit();
        $this->test->assertStringContainsString('COMMIT', $this->logger->lastMessage(LogLevel::DEBUG));
        $this->test->assertSame(0, $this->dbal->getTransactionLevel());

        $this->test->assertEquals($this->count + 3, $this->getRecordCount());
        $this->deleteRecords(1000, 1001, 1002);
    }

    public function testNestedRollback(): void
    {
        $this->dbal->transBegin();
        $this->insertRecord(1000);
        $this->dbal->transBegin();
        $this->insertRecord(1001);
        $this->dbal->transBegin();
        $this->insertRecord(1002);

        $this->test->assertEquals($this->count + 3, $this->getRecordCount());

        $this->dbal->transRollback();
        $this->test->assertStringContainsString('LEVEL_2', $this->logger->lastMessage(LogLevel::DEBUG));
        $this->test->assertSame(2, $this->dbal->getTransactionLevel());
        $this->test->assertEquals($this->count + 2, $this->getRecordCount());

        $this->dbal->transRollback();
        $this->test->assertStringContainsString('LEVEL_1', $this->logger->lastMessage(LogLevel::DEBUG));
        $this->test->assertSame(1, $this->dbal->getTransactionLevel());
        $this->test->assertEquals($this->count + 1, $this->getRecordCount());

        $this->dbal->transRollback();
        $this->test->assertStringContainsString('ROLLBACK', $this->logger->lastMessage(LogLevel::DEBUG));
        $this->test->assertSame(0, $this->dbal->getTransactionLevel());
        $this->test->assertEquals($this->count, $this->getRecordCount());
    }

    public function testNestedRollbackCommitRollback(): void
    {
        $this->dbal->transBegin();
        $this->insertRecord(1000);
        $this->dbal->transBegin();
        $this->insertRecord(1001);
        $this->dbal->transBegin();
        $this->insertRecord(1002);

        $this->dbal->transRollback();
        $this->test->assertEquals($this->count + 2, $this->getRecordCount());

        $this->dbal->transCommit();
        $this->test->assertEquals($this->count + 2, $this->getRecordCount());

        $this->dbal->transRollback();
        $this->test->assertEquals($this->count, $this->getRecordCount());
    }

    public function testNestedCommitRollbackCommit(): void
    {
        $this->dbal->transBegin();
        $this->insertRecord(1000);
        $this->dbal->transBegin();
        $this->insertRecord(1001);
        $this->dbal->transBegin();
        $this->insertRecord(1002);

        $this->dbal->transCommit();
        $this->test->assertEquals($this->count + 3, $this->getRecordCount());

        $this->dbal->transRollback();
        $this->test->assertEquals($this->count + 1, $this->getRecordCount());

        $this->dbal->transCommit();
        $this->test->assertEquals($this->count + 1, $this->getRecordCount());

        $this->deleteRecord(1000);
        $this->test->assertEquals($this->count, $this->getRecordCount());
    }

    private function getRecordCount(): int
    {
        return (int) $this->dbal->queryOne('SELECT COUNT(*) FROM albums', 0);
    }

    private function insertRecord(int $albumid): void
    {
        $sql = 'SELECT * FROM albums WHERE (albumid IS NULL);';
        $recordset = $this->test->createRecordset($sql, 'albums', ['albumid']);
        $recordset->addNew();
        $recordset->values = [
            'albumid' => $albumid,
            'title' => "Created $albumid",
            'votes' => 0,
            'lastview' => null,
            'isfree' => false,
            'collect' => 0,
        ];
        $recordset->update();
    }

    private function deleteRecord(int $albumid): void
    {
        $sql = 'DELETE FROM albums WHERE (albumid = ' . $this->dbal->sqlQuote($albumid, CommonTypes::TINT) . ');';
        $this->dbal->execute($sql, "Cannot remove record $albumid");
    }

    private function deleteRecords(int ...$albumids): void
    {
        foreach ($albumids as $albumid) {
            $this->deleteRecord($albumid);
        }
    }
}
