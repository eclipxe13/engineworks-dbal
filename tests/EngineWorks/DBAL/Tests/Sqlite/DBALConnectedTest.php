<?php
namespace EngineWorks\DBAL\Tests\Sqlite;

use EngineWorks\DBAL\Tests\RecordsetTester;
use EngineWorks\DBAL\Tests\TestCaseWithSqliteDatabase;

class DBALConnectedTest extends TestCaseWithSqliteDatabase
{
    public function testQueryOneWithValues()
    {
        $expected = 45;
        $value = $this->dbal->queryOne('SELECT COUNT(*) FROM albums;');

        $this->assertSame($expected, $value);
    }

    public function testQueryOneWithError()
    {
        $expected = -10;
        $value = $this->dbal->queryOne('SELECT NULL FROM albums WHERE (albumid = -1);', $expected);

        $this->assertSame($expected, $value);
    }

    public function testQueryArray()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid BETWEEN 1 AND 5);';
        $result = $this->dbal->queryArray($sql);
        $this->assertInternalType('array', $result);
        $this->assertCount(5, $result);

        $expectedRows = $this->convertArrayFixedValuesToStrings($this->getFixedValuesWithLabels(1, 5));
        $this->assertEquals($expectedRows, $result);
    }

    public function testRecordsetUsingTester()
    {
        $tester = new RecordsetTester($this, $this->dbal);
        $tester->execute();
    }
}
