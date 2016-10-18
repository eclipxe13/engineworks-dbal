<?php
namespace EngineWorks\DBAL\Tests\Mssql;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Recordset;
use EngineWorks\DBAL\Result;
use EngineWorks\DBAL\Tests\TestCaseWithMssqlDatabase;

class MssqlDbalConnectedTest extends TestCaseWithMssqlDatabase
{
    public function testQuoteMultibyte()
    {
        $text = 'á é í ó ú';
        $sql = 'SELECT ' . $this->dbal->sqlQuote($text, CommonTypes::TTEXT);
        $this->assertSame("SELECT '$text'", $sql);
        $this->assertSame($text, $this->dbal->queryOne($sql));
    }

    public function testQueryOneWithValues()
    {
        $expected = 45;
        $value = $this->dbal->queryOne('SELECT COUNT(*) FROM albums;');

        $this->assertEquals($expected, $value);
    }

    public function testQueryOneWithError()
    {
        $expected = -10;
        $value = $this->dbal->queryOne('SELECT NULL FROM albums WHERE (albumid = -1);', $expected);

        $this->assertSame($expected, $value);
    }

    public function testQueryRow()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid = 5);';
        $result = $this->dbal->queryRow($sql);
        $this->assertInternalType('array', $result);

        $expectedRows = $this->convertArrayFixedValuesToStrings($this->getFixedValuesWithLabels(5, 5));
        $this->assertEquals($expectedRows, [$result]);
    }

    public function testQueryArray()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid BETWEEN 1 AND 5);';
        $result = $this->dbal->queryArray($sql);
        $this->assertInternalType('array', $result);
        $this->assertCount(5, $result);

        $expectedRows = $this->getFixedValuesWithLabels(1, 5);
        $result = $this->convertArrayStringsToFixedValues($result);
        $this->assertEquals($expectedRows, $result);
    }

    public function testQueryResult()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid = 5);';
        $result = $this->dbal->queryResult($sql);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(1, $result->resultCount());
        // get first
        $fetchedFirst = $result->fetchRow();
        $this->assertInternalType('array', $fetchedFirst);
        // move and get first again
        $this->assertTrue($result->moveFirst());
        $fetchedSecond = $result->fetchRow();
        // test they are the same
        $this->assertEquals($fetchedFirst, $fetchedSecond);

        $expectedFields = [
            ['name' => 'albumid', 'commontype' => CommonTypes::TINT, 'table' => ''],
            ['name' => 'title', 'commontype' => CommonTypes::TTEXT, 'table' => ''],
            ['name' => 'votes', 'commontype' => CommonTypes::TINT, 'table' => ''],
            ['name' => 'lastview', 'commontype' => CommonTypes::TDATETIME, 'table' => ''],
            ['name' => 'isfree', 'commontype' => CommonTypes::TBOOL, 'table' => ''],
            ['name' => 'collect', 'commontype' => CommonTypes::TNUMBER, 'table' => ''],
        ];
        $this->assertEquals($expectedFields, $result->getFields());
    }

    public function testQueryRecordset()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid between 1 and 5);';
        $recordset = $this->dbal->queryRecordset($sql, 'albums', ['albumid']);
        $this->assertSame('albums', $recordset->getEntityName());
        $this->assertSame(['albumid'], $recordset->getIdFields());
        $this->assertInstanceOf(Recordset::class, $recordset);
        $this->assertSame(5, $recordset->getRecordCount());
        $this->assertFalse($recordset->eof());
        $this->assertInternalType('array', $recordset->values);
        $this->assertEquals(
            ['albumid', 'title', 'votes', 'lastview', 'isfree', 'collect'],
            array_keys($recordset->values)
        );
        $expectedRows = $this->getFixedValuesWithLabels(1, 5);
        $index = 0;
        while (! $recordset->eof()) {
            $expectedValues = $expectedRows[$index];
            $this->assertEquals($expectedValues, $recordset->values);
            $recordset->moveNext();
            $index = $index + 1;
        }
    }
}
