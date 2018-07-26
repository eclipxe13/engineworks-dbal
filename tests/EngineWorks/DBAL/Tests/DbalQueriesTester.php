<?php
namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\DBAL;
use PHPUnit\Framework\TestCase;

class DbalQueriesTester
{
    /** @var DBAL */
    private $dbal;

    public function __construct(DBAL $dbal)
    {
        $this->dbal = $dbal;
    }

    public function execute()
    {
        $this->testQueryOneWithValues();
    }

    private function testQueryOneWithValues()
    {
        $expected = 45;
        $value = $this->dbal->queryOne('SELECT COUNT(*) FROM albums;');

        TestCase::assertSame($expected, $value);
    }
}
