<?php
namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\DBAL;
use PHPUnit\Framework\Error\Notice;

/* @var $this \EngineWorks\DBAL\Tests\TestCaseWithDatabase */

trait TransactionsWithExceptionsTestTrait
{
    /** @return DBAL */
    protected abstract function getDbal();

    public function testCommitThrowsWarningWithOutBegin()
    {
        $this->expectException(Notice::class);
        $this->getDbal()->transCommit();
    }

    public function testRollbackThrowsWarningWithOutBegin()
    {
        $this->expectException(Notice::class);
        $this->getDbal()->transRollback();
    }
}
