<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\TesterTraits;

use EngineWorks\DBAL\DBAL;
use PHPUnit\Framework\Error\Notice;

trait TransactionsWithExceptionsTestTrait
{
    abstract protected function getDbal(): DBAL;

    public function testCommitThrowsWarningWithOutBegin(): void
    {
        $this->expectException(Notice::class);
        $this->getDbal()->transCommit();
    }

    public function testRollbackThrowsWarningWithOutBegin(): void
    {
        $this->expectException(Notice::class);
        $this->getDbal()->transRollback();
    }
}
