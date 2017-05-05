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
        if (version_compare(PHP_VERSION, '7.0', '<')) {
            $this->markTestSkipped('This test only runs on php 7.0 or higher');
        }
        $this->expectException(Notice::class);
        $this->getDbal()->transCommit();
    }

    public function testRollbackThrowsWarningWithOutBegin()
    {
        if (version_compare(PHP_VERSION, '7.0', '<')) {
            $this->markTestSkipped('This test only runs on php 7.0 or higher');
        }
        $this->expectException(Notice::class);
        $this->getDbal()->transRollback();
    }
}
