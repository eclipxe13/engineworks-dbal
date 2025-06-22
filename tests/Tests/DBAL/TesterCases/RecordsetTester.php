<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\TesterCases;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Iterators\RecordsetIterator;
use EngineWorks\DBAL\Recordset;
use EngineWorks\DBAL\Tests\WithDatabaseTestCase;
use Exception;
use RuntimeException;

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
        $this->testOriginalValues();
    }

    /** @param scalar|null $albumid */
    private function queryAlbumAsRecordset($albumid): Recordset
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
        $this->test->assertCount(45, $recordset);
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
     * @param array<string, scalar|null> $values
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
     * @param array<string, scalar|null> $values
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
     * @param array<string, scalar|null> $values
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
     * @param array<string, scalar|null> $values
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
        $overrideTypes = [
            'lastview' => CommonTypes::TDATETIME,
            'isfree' => CommonTypes::TBOOL,
        ];
        $sql = 'SELECT * FROM albums WHERE (albumid between 1 and 5);';
        $recordset = $this->test->createRecordset($sql, 'albums', ['albumid'], $overrideTypes);
        $this->test->assertSame('albums', $recordset->getEntityName());
        $this->test->assertSame(['albumid'], $recordset->getIdFields());
        $this->test->assertInstanceOf(Recordset::class, $recordset);
        $this->test->assertSame(5, $recordset->getRecordCount());
        $this->test->assertFalse($recordset->eof());
        $this->test->assertIsArray($recordset->values); /** @phpstan-ignore-line method.alreadyNarrowedType */
        $this->test->assertEquals(
            ['albumid', 'title', 'votes', 'lastview', 'isfree', 'collect'],
            array_keys($recordset->values)
        );
        $expectedRows = $this->test->getFixedValuesWithLabels(1, 5);
        $rows = iterator_to_array($recordset);
        $this->test->assertEquals($expectedRows, $rows);
    }

    public function testIteratorRewind(): void
    {
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
        $this->test->assertNotEquals($first, $iterator->current());
        $iterator->rewind();
        $this->test->assertEquals($first, $iterator->current());
    }

    public function testIteratorComposedKeys(): void
    {
        $createdRows = $this->test->getFixedValuesWithLabels(1, 2);
        $sql = 'SELECT * FROM albums WHERE (albumid between 1 and 2);';
        $recordset = $this->test->createRecordset($sql);

        $iterator = new RecordsetIterator($recordset, ['albumid', 'title'], ':');
        $this->test->assertTrue($iterator->valid());
        $this->test->assertSame($createdRows[0]['albumid'] . ':' . $createdRows[0]['title'], $iterator->key());
        $iterator->next();
        $this->test->assertSame($createdRows[1]['albumid'] . ':' . $createdRows[1]['title'], $iterator->key());
    }

    public function testOriginalValues(): void
    {
        $createdRow = $this->test->getFixedValuesWithLabels(1, 1)[0];

        $overrideTypes = [
            'lastview' => CommonTypes::TDATETIME,
            'isfree' => CommonTypes::TBOOL,
        ];
        $sql = 'SELECT * FROM albums WHERE (albumid = 1);';
        $recordset = $this->test->createRecordset($sql, 'albums', ['albumid'], $overrideTypes);

        $this->test->assertSame(1, $recordset->getOriginalValue('albumid'));
        $this->test->assertSame('default value', $recordset->getOriginalValue('non-existent-field', 'default value'));

        $originalValues = $recordset->getOriginalValues();
        $this->test->assertEquals($createdRow, $originalValues);

        $recordset->addNew();
        try {
            $recordset->getOriginalValues();
            throw new Exception('getOriginalValues did not throw expected exception');
        } catch (RuntimeException $exception) {
            $this->test->assertStringContainsString(
                'There are no original values',
                $exception->getMessage()
            );
        }
    }
}
