<?php
namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\DBAL;
use PHPUnit\Framework\Error\Notice;
use PHPUnit\Runner\Version;

/* @var $this \EngineWorks\DBAL\Tests\TestCaseWithDatabase */

trait TransactionsWithExceptionsTestTrait
{
    /** @return DBAL */
    abstract protected function getDbal();

    private function checkPhpUnitVersion($minimalVersion)
    {
        $phpUnitVersion = '5.6';
        if (class_exists(Version::class)) {
            $phpUnitVersion = Version::series();
        }
        if (version_compare($phpUnitVersion, $minimalVersion, '<')) {
            $this->markTestSkipped(sprintf('This test only runs on phpunit %s or higher', $minimalVersion));
        }
    }

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
