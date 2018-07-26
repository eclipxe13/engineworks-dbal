<?php
namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\DBAL;
use PHPUnit\Framework\Assert;

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
        $this->testQueryOneWithDefault();
    }

    private function testQueryOneWithValues()
    {
        $expected = 45;
        $value = $this->dbal->queryOne('SELECT COUNT(*) FROM albums;');

        Assert::assertEquals($expected, $value);
    }

    private function testQueryOneWithDefault()
    {
        $expected = -10;
        $value = $this->dbal->queryOne('SELECT 1 FROM albums WHERE (albumid = -1);', $expected);

        Assert::assertSame($expected, $value);
    }
}
