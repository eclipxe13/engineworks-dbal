<?php
namespace EngineWorks\DBAL\Tests\DBAL\Sqlsrv;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Iterators\ResultIterator;
use EngineWorks\DBAL\Result;
use EngineWorks\DBAL\Tests\SqlsrvWithDatabaseTestCase;

class SqlsrvResultTest extends SqlsrvWithDatabaseTestCase
{
    /** @var Result */
    protected $result;

    protected function setUp()
    {
        parent::setUp();
        if (! $this->result instanceof Result) {
            $this->result = $this->queryResult('SELECT * FROM albums WHERE (albumid between 1 and 3);');
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

    public function testFetchRowSequence()
    {
        $this->assertInternalType('array', $this->result->fetchRow());
        $this->assertInternalType('array', $this->result->fetchRow());
        $this->assertInternalType('array', $this->result->fetchRow());
        $this->assertSame(false, $this->result->fetchRow());
        $this->assertSame(false, $this->result->fetchRow());
    }

    public function testGetFields()
    {
        // cannot get (yet) the table from a query
        $expected = [
            [
                'name' => 'albumid',
                'commontype' => CommonTypes::TINT,
                'table' => 'albums',
            ],
            [
                'name' => 'title',
                'commontype' => CommonTypes::TTEXT,
                'table' => 'albums',
            ],
            [
                'name' => 'votes',
                'commontype' => CommonTypes::TINT,
                'table' => 'albums',
            ],
            [
                'name' => 'lastview',
                'commontype' => CommonTypes::TDATETIME,
                'table' => 'albums',
            ],
            [
                'name' => 'isfree',
                'commontype' => CommonTypes::TBOOL,
                'table' => 'albums',
            ],
            [
                'name' => 'collect',
                'commontype' => CommonTypes::TNUMBER,
                'table' => 'albums',
            ],
        ];
        $this->assertEquals($expected, $this->result->getFields());
    }

    public function testGetFieldsWithNoContents()
    {
        $result = $this->queryResult('SELECT albumid FROM albums WHERE (albumid = -1);');
        $fields = $result->getFields();
        $this->assertEquals(CommonTypes::TINT, $fields[0]['commontype']);
    }

    public function testGetIdFields()
    {
        $this->assertSame(false, $this->result->getIdFields(), 'Cannot get (yet) the Id Fields from a query');
    }

    public function testGetIterator()
    {
        $iterator = $this->result->getIterator();
        $this->assertInstanceOf(\Iterator::class, $iterator);
        $this->assertInstanceOf(ResultIterator::class, $iterator);
    }

    public function testMoveFirstAndMoveReturnFalseOnEmptyResultset()
    {
        $result = $this->queryResult('SELECT * FROM albums WHERE (albumid < 0);');
        $this->assertFalse($result->moveFirst());
        $this->assertFalse($result->moveTo(0));
        $this->assertFalse($result->moveTo(10));
    }

    public function testWithNoContents()
    {
        $result = $this->queryResult('SELECT albumid FROM albums WHERE (albumid = -1);');
        $fields = $result->getFields();
        $this->assertEquals(CommonTypes::TINT, $fields[0]['commontype']);
    }

    private function getForEach()
    {
        $array = [];
        foreach ($this->result as $key => $values) {
            $this->assertInternalType('array', $values);
            $array[$key] = $values;
        }
        return $array;
    }
}
