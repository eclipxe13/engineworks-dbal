<?php
namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Recordset;
use PHPUnit\Framework\TestCase;

class RecordsetTester
{
    /** @var TestCase */
    private $test;

    /** @var DBAL */
    private $dbal;

    public function __construct(TestCase $test, DBAL $dbal)
    {
        $this->test = $test;
        $this->dbal = $dbal;
    }

    public function execute()
    {
        // check connection exists
        if (! $this->dbal->isConnected()) {
            $this->test->markTestSkipped('The database is not connected');
            return;
        }
        $this->testIterator();
        $this->testQueryRecordsetOnNonExistent();
        $values = [
            'albumid' => 888,
            'title' => 'Inserting using Recordsets',
            'votes' => null,
            'lastview' => time(),
            'isfree' => false,
            'collect' => 987.65,
        ];
        $this->testRecordCount();
        $this->testAddNew($values);
        $this->testInsertedData($values);
        $this->testUpdate($values);
        $this->testDelete($values);
        $this->testRecordCount();
    }

    private function queryRecordset($albumid)
    {
        $sql = 'SELECT * FROM albums WHERE (albumid = ' . $this->dbal->sqlQuote($albumid, CommonTypes::TINT) . ');';
        return $this->dbal->queryRecordset($sql, 'albums', ['albumid']);
    }

    public function testRecordCount()
    {
        $sql = 'SELECT * FROM albums ORDER BY albumid;';
        $recordset = $this->dbal->queryRecordset($sql, 'albums', ['albumid']);
        $this->assertSame($sql, $recordset->getSource());
        $this->test->assertSame(45, $recordset->getRecordCount());
        for ($i = 1; $i <= 45; $i++) {
            $this->test->assertFalse($recordset->eof());
            $this->test->assertSame($i, $recordset->values['albumid']);
            $recordset->moveNext();
        }
        $this->test->assertTrue($recordset->eof());
    }

    public function testQueryRecordsetOnNonExistent()
    {
        $recordset = $this->queryRecordset(999);
        $this->test->assertInstanceOf(Recordset::class, $recordset);
        $this->test->assertTrue($recordset->eof());
        $this->test->assertSame('albums', $recordset->getEntityName());
        $this->test->assertSame(['albumid'], $recordset->getIdFields());
        $this->test->assertSame($recordset::RSMODE_CONNECTED_EDIT, $recordset->getMode());
        $this->test->assertTrue($recordset->canModify());
    }

    public function testAddNew($values)
    {
        $recordset = $this->queryRecordset($values['albumid']);
        $this->test->assertTrue($recordset->eof());
        $recordset->addNew();
        $this->test->assertSame($recordset::RSMODE_CONNECTED_ADDNEW, $recordset->getMode());
        $recordset->values = $values;
        $update = $recordset->update();
        $this->test->assertSame(1, $update);
    }

    public function testInsertedData($values)
    {
        $recordset = $this->queryRecordset($values['albumid']);
        $this->test->assertFalse($recordset->eof());
        foreach ($values as $key => $value) {
            $this->test->assertEquals($value, $recordset->values[$key]);
        }
    }

    public function testUpdate($values)
    {
        $recordset = $this->queryRecordset($values['albumid']);
        $this->test->assertFalse($recordset->eof());
        $this->test->assertNull($recordset->values['votes']);
        $recordset->values['votes'] = 55;
        $this->test->assertSame(1, $recordset->update());

        $recordset = $this->queryRecordset($values['albumid']);
        $this->test->assertSame(55, $recordset->values['votes']);
    }

    public function testDelete($values)
    {
        $recordset = $this->queryRecordset($values['albumid']);
        $this->test->assertFalse($recordset->eof());
        $this->test->assertSame(1, $recordset->delete());

        $recordset = $this->queryRecordset($values['albumid']);
        $this->test->assertTrue($recordset->eof());
    }

    public function testIterator()
    {
        /* @var \EngineWorks\DBAL\Tests\TestCaseWithDatabase $test */
        $test = $this->test;
        $overrideTypes = [
            'lastview' => CommonTypes::TDATETIME,
            'isfree' => CommonTypes::TBOOL,
        ];
        $sql = 'SELECT * FROM albums WHERE (albumid between 1 and 5);';
        $recordset = $this->dbal->queryRecordset($sql, 'albums', ['albumid'], $overrideTypes);
        $test->assertSame('albums', $recordset->getEntityName());
        $test->assertSame(['albumid'], $recordset->getIdFields());
        $test->assertInstanceOf(Recordset::class, $recordset);
        $test->assertSame(5, $recordset->getRecordCount());
        $test->assertFalse($recordset->eof());
        $test->assertInternalType('array', $recordset->values);
        $test->assertEquals(
            ['albumid', 'title', 'votes', 'lastview', 'isfree', 'collect'],
            array_keys($recordset->values)
        );
        $expectedRows = $test->getFixedValuesWithLabels(1, 5);
        $index = 0;
        foreach ($recordset as $iteratedValues) {
            $expectedValues = $expectedRows[$index];
            $test->assertEquals($expectedValues, $iteratedValues);
            $index = $index + 1;
        }
    }
}
