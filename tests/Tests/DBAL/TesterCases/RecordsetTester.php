<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\TesterCases;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Iterators\RecordsetIterator;
use EngineWorks\DBAL\Recordset;
use EngineWorks\DBAL\Tests\WithDatabaseTestCase;

final class RecordsetTester
{
    /** @var WithDatabaseTestCase */
    private $test;

    /** @var DBAL */
    private $dbal;

    public function __construct(WithDatabaseTestCase $test)
    {
        $this->test = $test;
        $this->dbal = $test->getDbal();
    }

    public function execute(): void
    {
        // check connection exists
        if (! $this->dbal->isConnected()) {
            $this->test->markTestSkipped('The database is not connected');
        }
        $this->testIteratorValues();
        $this->testIteratorRewind();
        $this->testIteratorComposedKeys();
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

    private function queryAlbumAsRecordset(int $albumid): Recordset
    {
        $sql = 'SELECT * FROM albums WHERE (albumid = ' . $this->dbal->sqlQuote($albumid, CommonTypes::TINT) . ');';
        return $this->test->createRecordset($sql, 'albums', ['albumid']);
    }

    public function testRecordCount(): void
    {
        $sql = 'SELECT * FROM albums ORDER BY albumid;';
        $recordset = $this->test->createRecordset($sql, 'albums', ['albumid']);
        $this->test->assertSame($sql, $recordset->getSource());
        $this->test->assertSame(45, $recordset->getRecordCount());
        for ($i = 1; $i <= 45; $i++) {
            $this->test->assertFalse($recordset->eof());
            $this->test->assertSame($i, $recordset->values['albumid']);
            $recordset->moveNext();
        }
        $this->test->assertTrue($recordset->eof());
    }

    public function testQueryRecordsetOnNonExistent(): void
    {
        $recordset = $this->queryAlbumAsRecordset(999);
        $this->test->assertInstanceOf(Recordset::class, $recordset);
        $this->test->assertTrue($recordset->eof());
        $this->test->assertSame('albums', $recordset->getEntityName());
        $this->test->assertSame(['albumid'], $recordset->getIdFields());
        $this->test->assertSame($recordset::RSMODE_CONNECTED_EDIT, $recordset->getMode());
        $this->test->assertTrue($recordset->canModify());
    }

    /**
     * @param array<string, mixed> $values
     */
    public function testAddNew(array $values): void
    {
        $recordset = $this->queryAlbumAsRecordset($values['albumid']);
        $this->test->assertTrue($recordset->eof());
        $recordset->addNew();
        $this->test->assertSame($recordset::RSMODE_CONNECTED_ADDNEW, $recordset->getMode());
        $recordset->values = $values;
        $update = $recordset->update();
        $this->test->assertSame(1, $update);
    }

    /**
     * @param array<string, mixed> $values
     */
    public function testInsertedData(array $values): void
    {
        $recordset = $this->queryAlbumAsRecordset($values['albumid']);
        $this->test->assertFalse($recordset->eof());
        foreach ($values as $key => $value) {
            $this->test->assertEquals($value, $recordset->values[$key]);
        }
    }

    /**
     * @param array<string, mixed> $values
     */
    public function testUpdate(array $values): void
    {
        $recordset = $this->queryAlbumAsRecordset($values['albumid']);
        $this->test->assertFalse($recordset->eof());
        $this->test->assertNull($recordset->values['votes']);
        $recordset->values['votes'] = 55;
        $this->test->assertSame(1, $recordset->update());

        $recordset = $this->queryAlbumAsRecordset($values['albumid']);
        $this->test->assertSame(55, $recordset->values['votes']);
    }

    /**
     * @param array<string, mixed> $values
     */
    public function testDelete(array $values): void
    {
        $recordset = $this->queryAlbumAsRecordset($values['albumid']);
        $this->test->assertFalse($recordset->eof());
        $this->test->assertSame(1, $recordset->delete());

        $recordset = $this->queryAlbumAsRecordset($values['albumid']);
        $this->test->assertTrue($recordset->eof());
    }

    public function testIteratorValues(): void
    {
        $test = $this->test;
        $overrideTypes = [
            'lastview' => CommonTypes::TDATETIME,
            'isfree' => CommonTypes::TBOOL,
        ];
        $sql = 'SELECT * FROM albums WHERE (albumid between 1 and 5);';
        $recordset = $this->test->createRecordset($sql, 'albums', ['albumid'], $overrideTypes);
        $test->assertSame('albums', $recordset->getEntityName());
        $test->assertSame(['albumid'], $recordset->getIdFields());
        $test->assertInstanceOf(Recordset::class, $recordset);
        $test->assertSame(5, $recordset->getRecordCount());
        $test->assertFalse($recordset->eof());
        $test->assertIsArray($recordset->values);
        $test->assertEquals(
            ['albumid', 'title', 'votes', 'lastview', 'isfree', 'collect'],
            array_keys($recordset->values)
        );
        $expectedRows = $test->getFixedValuesWithLabels(1, 5);
        $rows = iterator_to_array($recordset);
        $test->assertEquals($expectedRows, $rows);
    }

    public function testIteratorRewind(): void
    {
        $test = $this->test;
        $overrideTypes = [
            'lastview' => CommonTypes::TDATETIME,
            'isfree' => CommonTypes::TBOOL,
        ];
        $sql = 'SELECT * FROM albums WHERE (albumid between 1 and 5);';
        $recordset = $this->test->createRecordset($sql, 'albums', ['albumid'], $overrideTypes);

        /** @noinspection PhpUnhandledExceptionInspection */
        $iterator = $recordset->getIterator();
        $first = $iterator->current();
        $iterator->next();
        $test->assertNotEquals($first, $iterator->current());
        $iterator->rewind();
        $test->assertEquals($first, $iterator->current());
    }

    public function testIteratorComposedKeys(): void
    {
        $test = $this->test;
        $createdRows = $test->getFixedValuesWithLabels(1, 2);
        $overrideTypes = [
            'lastview' => CommonTypes::TDATETIME,
            'isfree' => CommonTypes::TBOOL,
        ];
        $sql = 'SELECT * FROM albums WHERE (albumid between 1 and 2);';
        $recordset = $this->test->createRecordset($sql);

        $iterator = new RecordsetIterator($recordset, ['albumid', 'title'], ':');
        $test->assertTrue($iterator->valid());
        $test->assertSame($createdRows[0]['albumid'] . ':' . $createdRows[0]['title'], $iterator->key());
        $iterator->next();
        $test->assertSame($createdRows[1]['albumid'] . ':' . $createdRows[1]['title'], $iterator->key());
    }
}
