<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\TesterTraits;

use EngineWorks\DBAL\DBAL;
use Throwable;

trait TransactionsPreventCommitTestTrait
{
    abstract protected function getDbal(): DBAL;

    public function testTransactionPreventCommitChangeStatus(): void
    {
        $dbal = $this->getDbal();

        $this->assertFalse($dbal->transPreventCommit());
        $this->assertFalse($dbal->transPreventCommit(true));
        $this->assertTrue($dbal->transPreventCommit(false));
        $this->assertFalse($dbal->transPreventCommit());
    }

    public function testTransactionPreventCommitError(): void
    {
        $dbal = $this->getDbal();

        $this->assertSame(0, $dbal->getTransactionLevel());
        $dbal->transPreventCommit(true);

        $dbal->transBegin();
        $dbal->transBegin();
        $dbal->transCommit();
        $lastCommitError = false;
        try {
            $dbal->transCommit();
        } catch (Throwable $exception) {
            $this->assertSame(
                'Try to call final commit with prevent commit enabled',
                $exception->getMessage(),
            );
            $lastCommitError = true;
        }
        $this->assertTrue($lastCommitError);
        $this->assertSame(1, $dbal->getTransactionLevel());
        $dbal->transPreventCommit(false);
        $dbal->transCommit();
        $this->assertSame(0, $dbal->getTransactionLevel());
    }
}
