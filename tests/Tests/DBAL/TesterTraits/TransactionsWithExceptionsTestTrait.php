<?php
namespace EngineWorks\DBAL\Tests\DBAL\TesterTraits;

use EngineWorks\DBAL\DBAL;
use PHPUnit\Framework\Error\Notice;

trait TransactionsWithExceptionsTestTrait
{
    abstract protected function getDbal(): DBAL;

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
