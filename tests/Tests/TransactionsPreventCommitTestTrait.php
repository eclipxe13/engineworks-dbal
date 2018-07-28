<?php
namespace EngineWorks\DBAL\Tests;

/** @var $this \EngineWorks\DBAL\Tests\TestCaseWithDatabase */
trait TransactionsPreventCommitTestTrait
{
    public function testTransactionPreventCommitChangeStatus()
    {
        $dbal = $this->getDbal();

        $this->assertFalse($dbal->transPreventCommit());
        $this->assertFalse($dbal->transPreventCommit(true));
        $this->assertTrue($dbal->transPreventCommit(false));
        $this->assertFalse($dbal->transPreventCommit());
    }

    public function testTransactionPreventCommitError()
    {
        $dbal = $this->getDbal();

        $this->checkPhpUnitVersion('6.0');
        $this->assertSame(0, $dbal->getTransactionLevel());
        $dbal->transPreventCommit(true);

        $dbal->transBegin();
        $dbal->transBegin();
        $dbal->transCommit();
        $lastCommitError = false;
        try {
            $dbal->transCommit();
        } catch (\Throwable $exception) {
            $lastCommitError = true;
        }
        $this->assertTrue($lastCommitError);
        $this->assertSame(1, $dbal->getTransactionLevel());
        $dbal->transPreventCommit(false);
        $dbal->transCommit();
        $this->assertSame(0, $dbal->getTransactionLevel());
    }
}
