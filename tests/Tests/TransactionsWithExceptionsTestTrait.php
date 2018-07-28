<?php
namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\DBAL;
use PHPUnit\Framework\Error\Notice;

trait TransactionsWithExceptionsTestTrait
{
    abstract protected function getDbal(): DBAL;

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
