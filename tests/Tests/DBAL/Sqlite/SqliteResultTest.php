<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\Sqlite;

use Countable;
use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Iterators\ResultIterator;
use EngineWorks\DBAL\Result;
use EngineWorks\DBAL\Tests\SqliteWithDatabaseTestCase;
use Iterator;
use Traversable;

class SqliteResultTest extends SqliteWithDatabaseTestCase
{
    /** @var Result */
    private $result;

    protected function setUp(): void
    {
        parent::setUp();
        $this->result = $this->queryResult('SELECT * FROM albums WHERE (albumid between 1 and 3);');
    }

    public function testResultCount(): void
    {
        $this->assertSame(3, $this->result->resultCount());
    }

    public function testQueryResult(): void
    {
        $this->assertInstanceOf(Result::class, $this->result);
        $this->assertInstanceOf(Countable::class, $this->result);
        $this->assertInstanceOf(Traversable::class, $this->result);
        $this->assertCount(3, $this->result);
    }

    public function testIterator(): void
    {
        $fist = $this->getForEach();
        $this->assertCount(3, $fist);
        $second = $this->getForEach();
        $this->assertCount(3, $second);
        $this->assertEquals($fist, $second);
    }

    public function testIteratorAfterMovingTheFirstRecord(): void
    {
        $this->result->fetchRow();
        $contents = $this->getForEach();
        $this->assertCount(3, $contents);
    }

    public function testMoveToOutOfBounds(): void
    {
        $this->assertFalse($this->result->moveTo(-10));
        $this->assertFalse($this->result->moveTo(10000));
    }

    public function testMoveOffSet(): void
    {
        $current = $this->getForEach();
        $this->assertSame(false, $this->result->fetchRow(), 'After for each fetch must return FALSE');
        $this->result->moveFirst();
        $this->assertSame($current[0], $this->result->fetchRow(), 'After move first fetch must return the first row');
        $this->assertTrue($this->result->moveTo(0));
        $this->assertSame($current[0], $this->result->fetchRow(), 'After move to index 0 fetch must return first row');
        $this->assertTrue($this->result->moveTo(2));
        $this->assertSame($current[2], $this->result->fetchRow(), 'After move to index 2 fetch must return last row');
        $this->assertTrue($this->result->moveTo(1));
        $this->assertSame($current[1], $this->result->fetchRow(), 'After move to index 1 fetch must return second row');
    }

    public function testFetchRowSequence(): void
    {
        // this is made to check undocumented behavior on SQLite3Result::fetchArray
        // http://php.net/manual/en/sqlite3result.fetcharray.php#115856
        $this->assertIsArray($this->result->fetchRow());
        $this->assertIsArray($this->result->fetchRow());
        $this->assertIsArray($this->result->fetchRow());
        $this->assertSame(false, $this->result->fetchRow(), 'First fetch row on EOF must return FALSE');
        $this->assertSame(false, $this->result->fetchRow(), 'Second fetch row on EOF must return FALSE');
        $this->assertSame(false, $this->result->fetchRow(), 'Third fetch row on EOF must return FALSE');
    }

    public function testGetFields(): void
    {
        // cannot get (yet) the table from a query
        $expected = [
            [
                'name' => 'albumid',
                'commontype' => CommonTypes::TINT,
                'table' => '',
            ],
            [
                'name' => 'title',
                'commontype' => CommonTypes::TTEXT,
                'table' => '',
            ],
            [
                'name' => 'votes',
                'commontype' => CommonTypes::TINT,
                'table' => '',
            ],
            [
                'name' => 'lastview',
                'commontype' => CommonTypes::TTEXT,
                'table' => '',
            ],
            [
                'name' => 'isfree',
                'commontype' => CommonTypes::TINT,
                'table' => '',
            ],
            [
                'name' => 'collect',
                'commontype' => CommonTypes::TNUMBER,
                'table' => '',
            ],
        ];
        $this->assertEquals($expected, $this->result->getFields());
    }

    public function testGetFieldsWithNoContents(): void
    {
        $this->markTestSkipped('Already know that Sqlite fail when the result does not have contents');
        // $result = $this->queryResult('SELECT albumid FROM albums WHERE (albumid = -1);');
        // $fields = $result->getFields();
        // $this->assertEquals(CommonTypes::TINT, $fields[0]['commontype']);
    }

    public function testGetIdFields(): void
    {
        $this->assertSame(false, $this->result->getIdFields(), 'Cannot get (yet) the Id Fields from a query');
    }

    public function testGetIterator(): void
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $iterator = $this->result->getIterator();
        $this->assertInstanceOf(Iterator::class, $iterator);
        $this->assertInstanceOf(ResultIterator::class, $iterator);
    }

    public function testMoveFirstAndMoveReturnFalseOnEmptyResultset(): void
    {
        $result = $this->queryResult('SELECT * FROM albums WHERE (albumid < 0);');
        $this->assertFalse($result->moveFirst());
        $this->assertFalse($result->moveTo(0));
        $this->assertFalse($result->moveTo(10));
    }

    /**
     * Iterates the result and assert that each item is an array
     *
     * @return array<int, mixed[]>
     */
    private function getForEach(): array
    {
        $array = [];
        foreach ($this->result as $key => $values) {
            $this->assertIsArray($values);
            $array[$key] = $values;
        }
        return $array;
    }
}
