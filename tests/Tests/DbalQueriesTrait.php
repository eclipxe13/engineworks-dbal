<?php
namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Result;
use EngineWorks\DBAL\Tests\Sample\ArrayLogger;

/* @var $this \EngineWorks\DBAL\Tests\TestCaseWithDatabase */

trait DbalQueriesTrait
{
    /** @return DBAL */
    abstract protected function getDbal();

    abstract protected function getLogger(): ArrayLogger;

    public function testDisconnectAndReconnect()
    {
        // $dbal is already connected
        $dbal = $this->getDbal();

        $this->assertTrue($dbal->isConnected());

        $dbal->disconnect();
        $this->assertFalse($dbal->isConnected());

        $this->assertTrue($dbal->connect());
        $this->assertTrue($dbal->isConnected());

        $logger = $this->getLogger();
        $this->assertSame($logger->messages('info', true), $logger->allMessages(), 'All messages should be level info');
    }

    public function testQuoteAndQueryMultibyte()
    {
        $text = 'á é í ó ú ñ ü € ¢ “” ½';
        $sql = 'SELECT ' . $this->getDbal()->sqlQuote($text, CommonTypes::TTEXT);
        $this->assertSame("SELECT '$text'", $sql);
        $this->assertSame($text, $this->getDbal()->queryOne($sql));
    }

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

    public function testQueryRowWithNoValues()
    {
        $this->assertFalse(
            $this->getDbal()->queryRow('SELECT * FROM albums WHERE (albumid = -1);')
        );
    }

    public function testQueryRowWithError()
    {
        $this->assertFalse(
            $this->getDbal()->queryRow('SELECT 1 FROM nonexistent;')
        );
    }

    public function testQueryArray()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid BETWEEN 4 AND 5);';
        $result = $this->getDbal()->queryArray($sql);
        $this->assertInternalType('array', $result);
        $this->assertCount(2, $result);
        $result = $this->convertArrayStringsToFixedValues($result);

        $expectedRows = $this->getFixedValuesWithLabels(4, 5);
        $this->assertEquals($expectedRows, $result);
    }

    public function testQueryArrayWithError()
    {
        $this->assertFalse(
            $this->getDbal()->queryRow('SELECT 1 FROM nonexistent;')
        );
    }

    public function testQueryArrayWithNoValues()
    {
        $array = $this->getDbal()->queryArray('SELECT * FROM albums WHERE 1 = 2;');
        $this->assertSame([], $array);
    }

    public function testQueryValues()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid = 5);';
        $values = $this->getDbal()->queryValues($sql, $this->overrideTypes());
        $this->assertInternalType('array', $values);

        $expectedValues = $this->getFixedValuesWithLabels(5, 5)[0];
        $this->assertEquals($expectedValues, $values);
    }

    public function testQueryValuesWithNoValues()
    {
        $this->assertFalse(
            $this->getDbal()->queryValues('SELECT * FROM albums WHERE (1 = 2);', $this->overrideTypes())
        );
    }

    public function testQueryValuesWithError()
    {
        $this->assertFalse(
            $this->getDbal()->queryValues('SELECT * FROM nonexistent;', $this->overrideTypes())
        );
    }

    public function testQueryArrayValues()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid BETWEEN 1 AND 5) ORDER BY albumid;';
        $arrayValues = $this->getDbal()->queryArrayValues($sql, $this->overrideTypes());
        $this->assertInternalType('array', $arrayValues);
        $this->assertCount(5, $arrayValues);

        $values = $arrayValues[0];
        $this->assertInternalType('array', $values);

        $expectedValues = $this->getFixedValuesWithLabels(1, 1)[0];
        $this->assertEquals($expectedValues, $values);
    }

    public function testQueryArrayValuesWithNoValues()
    {
        $sql = 'SELECT * FROM albums WHERE (1 = 2);';
        $arrayValues = $this->getDbal()->queryArrayValues($sql, $this->overrideTypes());
        $this->assertSame([], $arrayValues);
    }

    public function testQueryArrayValuesWithError()
    {
        $this->assertFalse(
            $this->getDbal()->queryArrayValues('SELECT * FROM nonexistent;', $this->overrideTypes())
        );
    }

    public function testQueryArrayKey()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid BETWEEN 1 AND 3) ORDER BY albumid;';
        $results = $this->getDbal()->queryArrayKey($sql, 'albumid', 'X');
        $this->assertInternalType('array', $results);
        $this->assertCount(3, $results);

        $this->assertSame(['X1', 'X2', 'X3'], array_keys($results));
        foreach ($results as $key => $values) {
            $this->assertSame('X' . $values['albumid'], $key);
        }
    }

    public function testQueryArrayKeyWithNoValues()
    {
        $sql = 'SELECT * FROM albums WHERE (1 = 2);';
        $results = $this->getDbal()->queryArrayKey($sql, 'albumid');
        $this->assertSame([], $results);
    }

    public function testQueryArrayKeyWithError()
    {
        $sql = 'SELECT * FROM nonexistent;';
        $results = $this->getDbal()->queryArrayKey($sql, 'albumid');
        $this->assertFalse($results);
    }

    public function testQueryArrayKeyWithInvalidKey()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid between 1 and 3);';
        $results = $this->getDbal()->queryArrayKey($sql, 'non-existent-key');
        $this->assertFalse($results);
    }

    public function testQueryPairs()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid between 2 and 5);';
        $pairs = $this->getDbal()->queryPairs($sql, 'albumid', 'title', 'X');
        $expectedPairs = call_user_func(function ($originalRows): array {
            $converted = [];
            foreach ($originalRows as $originalRow) {
                $converted['X' . $originalRow['albumid']] = $originalRow['title'];
            }
            return $converted;
        }, $this->getFixedValuesWithLabels(2, 5));

        $this->assertSame($expectedPairs, $pairs);
    }

    public function testQueryPairsWithNonExistentField()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid between 2 and 3);';
        $pairs = $this->getDbal()->queryPairs($sql, 'albumid', 'name', '', 'not-found');
        $expectedPairs = [
            2 => 'not-found',
            3 => 'not-found',
        ];
        $this->assertSame($expectedPairs, $pairs);
    }

    public function testQueryPairsWithError()
    {
        $sql = 'SELECT * FROM nonexistent;';
        $pairs = $this->getDbal()->queryPairs($sql, 'foo', 'bar');
        $this->assertSame([], $pairs);
    }

    public function testQueryArrayOne()
    {
        $sql = 'SELECT albumid FROM albums WHERE (albumid between 2 and 3);';
        $values = $this->getDbal()->queryArrayOne($sql);
        $expectedValues = [2, 3];
        $this->assertEquals($expectedValues, $values);
    }

    public function testQueryArrayOneWithFieldName()
    {
        $sql = 'SELECT title, albumid FROM albums WHERE (albumid between 2 and 3);';
        $values = $this->getDbal()->queryArrayOne($sql, 'albumid');
        $expectedValues = [2, 3];
        $this->assertEquals($expectedValues, $values);
    }

    public function testQueryArrayOneWithInvalidFieldName()
    {
        $sql = 'SELECT * FROM albums WHERE (albumid between 2 and 3);';
        $this->assertFalse($this->getDbal()->queryArrayOne($sql, 'foo'));
    }

    public function testQueryArrayOneWithError()
    {
        $this->assertFalse(
            $this->getDbal()->queryArrayOne('SELECT * from nonexistent;')
        );
    }

    public function testQueryOnString()
    {
        $sql = 'SELECT albumid, title FROM albums WHERE (albumid between 1 and 3);';
        $value = $this->getDbal()->queryOnString($sql, 'default', ' * ');
        $expectedValue = '1 * 2 * 3';
        $this->assertSame($expectedValue, $value);
    }

    public function testQueryOnStringUsingDefaults()
    {
        $sql = 'SELECT albumid, title FROM albums WHERE (albumid between 1 and 3);';
        $value = $this->getDbal()->queryOnString($sql);
        $expectedValue = '1, 2, 3';
        $this->assertSame($expectedValue, $value);
    }

    public function testQueryOnStringWithError()
    {
        $sql = 'SELECT * FROM nonexistent';
        $expectedValue = 'default';
        $value = $this->getDbal()->queryOnString($sql, $expectedValue);
        $this->assertSame($expectedValue, $value);
    }

    // override if needed
    public function overrideTypes(): array
    {
        return [];
    }

    // override if needed
    public function overrideEntity(): string
    {
        return '';
    }

    public function testExecuteWithError()
    {
        $expectedMessage = 'Invalid SQL Statement';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->getDbal()->execute('BAD STATEMENT;', $expectedMessage);
    }

    public function testQueryResult()
    {
        $expectedTablename = $this->overrideEntity();
        $sql = 'SELECT * FROM albums WHERE (albumid = 5);';
        /* @var \EngineWorks\DBAL\Result $result */
        $result = $this->getDbal()->queryResult($sql, $this->overrideTypes());
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
            ['name' => 'albumid', 'commontype' => CommonTypes::TINT, 'table' => $expectedTablename],
            ['name' => 'title', 'commontype' => CommonTypes::TTEXT, 'table' => $expectedTablename],
            ['name' => 'votes', 'commontype' => CommonTypes::TINT, 'table' => $expectedTablename],
            ['name' => 'lastview', 'commontype' => CommonTypes::TDATETIME, 'table' => $expectedTablename],
            ['name' => 'isfree', 'commontype' => CommonTypes::TBOOL, 'table' => $expectedTablename],
            ['name' => 'collect', 'commontype' => CommonTypes::TNUMBER, 'table' => $expectedTablename],
        ];

        $this->assertArraySubset($expectedFields, $result->getFields());
    }
}
