<?php
namespace EngineWorks\DBAL\Tests\Sqlite;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Iterators\ResultIterator;
use EngineWorks\DBAL\Result;
use EngineWorks\DBAL\Tests\TestCaseWithSqliteDatabase;

class ResultTest extends TestCaseWithSqliteDatabase
{
    /** @var Result */
    private $result;

    protected function setUp()
    {
        parent::setUp();
        if (! ($this->result instanceof Result)) {
            $this->result = $this->dbal->queryResult(
                'SELECT * FROM albums WHERE (albumid between 1 and 3);'
            );
        }
    }

    public function testResultCount()
    {
        $this->assertSame(3, $this->result->resultCount());
    }

    public function testQueryResult()
    {
        $this->assertInstanceOf(Result::class, $this->result);
        $this->assertInstanceOf(\Countable::class, $this->result);
        $this->assertInstanceOf(\Traversable::class, $this->result);
        $this->assertCount(3, $this->result);
    }

    public function testIterator()
    {
        $fist = $this->getForEach();
        $this->assertCount(3, $fist);
        $second = $this->getForEach();
        $this->assertCount(3, $second);
        $this->assertEquals($fist, $second);
    }

    public function testIteratorAfterMovingTheFirstRecord()
    {
        $this->result->fetchRow();
        $contents = $this->getForEach();
        $this->assertCount(3, $contents);
    }

    public function testMoveToOutOfBounds()
    {
        $this->assertFalse($this->result->moveTo(-10));
        $this->assertFalse($this->result->moveTo(10000));
    }

    public function testMoveOffSet()
    {
        $current = $this->getForEach();
        $this->assertSame($current[0], $this->result->fetchRow(), 'After for each fetch must return the first row');
        $this->assertTrue($this->result->moveTo(0));
        $this->assertSame($current[0], $this->result->fetchRow(), 'After move to index 0 fetch must return first row');
        $this->assertTrue($this->result->moveTo(2));
        $this->assertSame($current[2], $this->result->fetchRow(), 'After move to index 2 fetch must return last row');
        $this->assertTrue($this->result->moveTo(1));
        $this->assertSame($current[1], $this->result->fetchRow(), 'After move to index 1 fetch must return second row');
    }

    public function testGetFields()
    {
        // cannot get (yet) the table from a query
        $expected = [
            [
                'name' => 'albumid',
                'commontype' => CommonTypes::TINT,
                'table' => '',
                'flags' => null,
            ],
            [
                'name' => 'title',
                'commontype' => CommonTypes::TTEXT,
                'table' => '',
                'flags' => null,
            ],
            [
                'name' => 'votes',
                'commontype' => CommonTypes::TINT,
                'table' => '',
                'flags' => null,
            ],
            [
                'name' => 'lastview',
                'commontype' => CommonTypes::TTEXT,
                'table' => '',
                'flags' => null,
            ],
            [
                'name' => 'isfree',
                'commontype' => CommonTypes::TINT,
                'table' => '',
                'flags' => null,
            ],
            [
                'name' => 'collect',
                'commontype' => CommonTypes::TNUMBER,
                'table' => '',
                'flags' => null,
            ],
        ];
        $this->result->fetchRow();
        $this->assertEquals($expected, $this->result->getFields());
    }

    public function testGetIdFields()
    {
        $this->result->fetchRow();
        $this->assertEquals(false, $this->result->getIdFields(), 'Cannot get (yet) the Id Fields from a query');
    }

    public function testGetIterator()
    {
        $iterator = $this->result->getIterator();
        $this->assertInstanceOf(\Iterator::class, $iterator);
        $this->assertInstanceOf(ResultIterator::class, $iterator);
    }

    public function testMoveFirstAndMoveReturnFalseOnEmptyResultset()
    {
        $result = $this->dbal->queryResult('SELECT * FROM albums WHERE (albumid < 0);');
        $this->assertFalse($result->moveFirst());
        $this->assertFalse($result->moveTo(0));
        $this->assertFalse($result->moveTo(10));
    }

    private function getForEach()
    {
        $array = [];
        foreach ($this->result as $values) {
            $this->assertInternalType('array', $values);
            $array[] = $values;
        }
        return $array;
    }
}
