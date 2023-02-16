<?php

/** @noinspection PhpUsageOfSilenceOperatorInspection */

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\TesterTraits;

use EngineWorks\DBAL\DBAL;

trait TransactionsWithExceptionsTestTrait
{
    abstract protected function getDbal(): DBAL;

    public function testCommitThrowsWarningWithOutBegin(): void
    {
        error_clear_last();
        @$this->getDbal()->transCommit();
        $error = error_get_last() ?: [];
        $this->assertSame(E_USER_NOTICE, intval($error['type'] ?? 0));
    }

    public function testRollbackThrowsWarningWithOutBegin(): void
    {
        error_clear_last();
        @$this->getDbal()->transRollback();
        $error = error_get_last() ?: [];
        $this->assertSame(E_USER_NOTICE, intval($error['type'] ?? 0));
    }
}
