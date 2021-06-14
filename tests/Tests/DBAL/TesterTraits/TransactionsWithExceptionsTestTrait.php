<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\TesterTraits;

use EngineWorks\DBAL\DBAL;

trait TransactionsWithExceptionsTestTrait
{
    abstract protected function getDbal(): DBAL;

    public function testCommitThrowsWarningWithOutBegin(): void
    {
        $this->expectNotice();
        $this->getDbal()->transCommit();
    }

    public function testRollbackThrowsWarningWithOutBegin(): void
    {
        $this->expectNotice();
        $this->getDbal()->transRollback();
    }
}
