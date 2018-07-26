<?php
namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\DBAL;

/* @var $this \EngineWorks\DBAL\Tests\TestCaseWithDatabase */

trait QueriesTestTrait
{
    /** @return DBAL */
    abstract protected function getDbal();

    public function testQueryOneWithValues()
    {
        $expected = 45;
        $value = $this->getDbal()->queryOne('SELECT COUNT(*) FROM albums;');

        $this->assertEquals($expected, $value);
    }

    public function testQueryOneWithDefault()
    {
        $expected = -10;
        $value = $this->getDbal()->queryOne('SELECT 1 FROM albums WHERE (albumid = -1);', $expected);

        $this->assertSame($expected, $value);
    }

    public function testQueryRow()
    {
        $expectedRows = $this->convertArrayFixedValuesToStrings($this->getFixedValuesWithLabels(5, 5));

        $sql = 'SELECT * FROM albums WHERE (albumid = 5);';
        $result = $this->getDbal()->queryRow($sql);
        $this->assertInternalType('array', $result);
        $this->assertEquals($expectedRows, [$result]);
    }

    public function testQueryArray()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid BETWEEN 1 AND 5);';
        $result = $this->getDbal()->queryArray($sql);
        $this->assertInternalType('array', $result);
        $this->assertCount(5, $result);

        $expectedRows = $this->getFixedValuesWithLabels(1, 5);
        $result = $this->convertArrayStringsToFixedValues($result);
        $this->assertEquals($expectedRows, $result);
    }
}
