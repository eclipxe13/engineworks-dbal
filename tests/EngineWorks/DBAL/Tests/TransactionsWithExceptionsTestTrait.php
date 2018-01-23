<?php
namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\DBAL;
use PHPUnit\Framework\Error\Notice;

/* @var $this \EngineWorks\DBAL\Tests\TestCaseWithDatabase */

trait TransactionsWithExceptionsTestTrait
{
    /** @return DBAL */
    abstract protected function getDbal();

    public function testCommitThrowsWarningWithOutBegin()
    {
        $this->checkPhpUnitVersion('6.0');
        $this->expectException(Notice::class);
        $this->getDbal()->transCommit();
    }

    public function testRollbackThrowsWarningWithOutBegin()
    {
        $this->checkPhpUnitVersion('6.0');
        $this->expectException(Notice::class);
        $this->getDbal()->transRollback();
    }
}
