<?php
namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\DBAL;

/* @var $this \EngineWorks\DBAL\Tests\TestCaseWithDatabase */

trait TransactionsPreventCommitTestTrait
{
    /** @return DBAL */
    abstract protected function getDbal();

    public function testTransactionPreventCommitChangeStatus()
    {
        $dbal = $this->getDbal();

        $this->assertSame(false, $dbal->transPreventCommit());
        $this->assertSame(false, $dbal->transPreventCommit(true));
        $this->assertSame(true, $dbal->transPreventCommit(false));
        $this->assertSame(false, $dbal->transPreventCommit());
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
        $this->assertSame(true, $lastCommitError);
        $this->assertSame(1, $dbal->getTransactionLevel());
        $dbal->transPreventCommit(false);
        $dbal->transCommit();
        $this->assertSame(0, $dbal->getTransactionLevel());
    }
}
