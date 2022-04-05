<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\TesterTraits;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Exceptions\QueryException;
use EngineWorks\DBAL\Result;
use EngineWorks\DBAL\Tests\DBAL\Sample\ArrayLogger;
use EngineWorks\DBAL\Tests\WithDatabaseTestCase;
use RuntimeException;

/** @var WithDatabaseTestCase $this */
trait DbalQueriesTrait
{
    abstract protected function getDbal(): DBAL;

    abstract protected function getLogger(): ArrayLogger;

    public function testDisconnectAndReconnect(): void
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

    public function testQuoteAndQueryMultibyte(): void
    {
        $text = 'á é í ó ú ñ ü € ¢ “” ½';
        $sql = 'SELECT ' . $this->getDbal()->sqlQuote($text, CommonTypes::TTEXT);
        $this->assertSame("SELECT '$text'", $sql);
        $this->assertSame($text, $this->getDbal()->queryOne($sql));
    }

    public function testQueryOneWithValues(): void
    {
        $expected = 45;
        $value = $this->getDbal()->queryOne('SELECT COUNT(*) FROM albums;');

        $this->assertEquals($expected, $value);
    }

    public function testQueryOneWithDefault(): void
    {
        $expected = -10;
        $value = $this->getDbal()->queryOne('SELECT 1 FROM albums WHERE (albumid = -1);', $expected);

        $this->assertSame($expected, $value);
    }

    public function testQueryRow(): void
    {
        $expectedRows = $this->convertArrayFixedValuesToStrings($this->getFixedValuesWithLabels(5, 5));

        $sql = 'SELECT * FROM albums WHERE (albumid = 5);';
        $result = $this->getDbal()->queryRow($sql);
        $this->assertIsArray($result);
        $this->assertEquals($expectedRows, [$result]);
    }

    public function testQueryRowWithNoValues(): void
    {
        $row = $this->getDbal()->queryRow('SELECT * FROM albums WHERE (albumid = -1);');
        $this->assertSame(false, $row);
    }

    public function testQueryRowWithError(): void
    {
        $row = $this->getDbal()->queryRow('SELECT 1 FROM nonexistent;');
        $this->assertSame(false, $row);
    }

    public function testQueryArray(): void
    {
        $sql = 'SELECT * FROM albums WHERE (albumid BETWEEN 4 AND 5);';
        $result = $this->getDbal()->queryArray($sql);
        $this->assertIsArray($result);
        $result = $result ?: [];
        $this->assertCount(2, $result);
        $result = $this->convertArrayStringsToFixedValues($result);

        $expectedRows = $this->getFixedValuesWithLabels(4, 5);
        $this->assertEquals($expectedRows, $result);
    }

    public function testQueryArrayWithError(): void
    {
        $array = $this->getDbal()->queryArray('SELECT 1 FROM nonexistent;');
        $this->assertSame(false, $array);
    }

    public function testQueryArrayWithNoValues(): void
    {
        $array = $this->getDbal()->queryArray('SELECT * FROM albums WHERE 1 = 2;');
        $this->assertSame([], $array);
    }

    public function testQueryValues(): void
    {
        $sql = 'SELECT * FROM albums WHERE (albumid = 5);';
        $values = $this->getDbal()->queryValues($sql, $this->overrideTypes());
        $this->assertIsArray($values);

        $expectedValues = $this->getFixedValuesWithLabels(5, 5)[0];
        $this->assertEquals($expectedValues, $values);
    }

    public function testQueryValuesWithNoValues(): void
    {
        $values = $this->getDbal()->queryValues('SELECT * FROM albums WHERE (1 = 2);', $this->overrideTypes());
        $this->assertSame(false, $values);
    }

    public function testQueryValuesWithError(): void
    {
        $values = $this->getDbal()->queryValues('SELECT * FROM nonexistent;', $this->overrideTypes());
        $this->assertSame(false, $values);
    }

    public function testQueryArrayValues(): void
    {
        $sql = 'SELECT * FROM albums WHERE (albumid BETWEEN 1 AND 5) ORDER BY albumid;';
        $arrayValues = $this->getDbal()->queryArrayValues($sql, $this->overrideTypes()) ?: [];
        $this->assertIsArray($arrayValues);
        $this->assertCount(5, $arrayValues);

        $values = $arrayValues[0];
        $this->assertIsArray($values);

        $expectedValues = $this->getFixedValuesWithLabels(1, 1)[0];
        $this->assertEquals($expectedValues, $values);
    }

    public function testQueryArrayValuesWithNoValues(): void
    {
        $sql = 'SELECT * FROM albums WHERE (1 = 2);';
        $arrayValues = $this->getDbal()->queryArrayValues($sql, $this->overrideTypes());
        $this->assertSame([], $arrayValues);
    }

    public function testQueryArrayValuesWithError(): void
    {
        $values = $this->getDbal()->queryArrayValues('SELECT * FROM nonexistent;', $this->overrideTypes());
        $this->assertSame(false, $values);
    }

    public function testQueryArrayKey(): void
    {
        $sql = 'SELECT * FROM albums WHERE (albumid BETWEEN 1 AND 3) ORDER BY albumid;';
        $results = $this->getDbal()->queryArrayKey($sql, 'albumid', 'X');
        $this->assertIsArray($results);
        $results = $results ?: [];
        $this->assertCount(3, $results);

        $this->assertSame(['X1', 'X2', 'X3'], array_keys($results));
        foreach ($results as $key => $values) {
            $this->assertSame('X' . $values['albumid'], $key);
        }
    }

    public function testQueryArrayKeyWithNoValues(): void
    {
        $sql = 'SELECT * FROM albums WHERE (1 = 2);';
        $results = $this->getDbal()->queryArrayKey($sql, 'albumid');
        $this->assertSame([], $results);
    }

    public function testQueryArrayKeyWithError(): void
    {
        $results = $this->getDbal()->queryArrayKey('SELECT * FROM nonexistent;', 'albumid');
        $this->assertSame(false, $results);
    }

    public function testQueryArrayKeyWithInvalidKey(): void
    {
        $sql = 'SELECT * FROM albums WHERE (albumid between 1 and 3);';
        $results = $this->getDbal()->queryArrayKey($sql, 'non-existent-key');
        $this->assertSame(false, $results);
    }

    public function testQueryPairs(): void
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

    public function testQueryPairsWithNonExistentField(): void
    {
        $sql = 'SELECT * FROM albums WHERE (albumid between 2 and 3);';
        $pairs = $this->getDbal()->queryPairs($sql, 'albumid', 'name', '', 'not-found');
        $expectedPairs = [
            2 => 'not-found',
            3 => 'not-found',
        ];
        $this->assertSame($expectedPairs, $pairs);
    }

    public function testQueryPairsWithError(): void
    {
        $sql = 'SELECT * FROM nonexistent;';
        $pairs = $this->getDbal()->queryPairs($sql, 'foo', 'bar');
        $this->assertSame([], $pairs);
    }

    public function testQueryArrayOne(): void
    {
        $sql = 'SELECT albumid FROM albums WHERE (albumid between 2 and 3);';
        $values = $this->getDbal()->queryArrayOne($sql);
        $expectedValues = [2, 3];
        $this->assertEquals($expectedValues, $values);
    }

    public function testQueryArrayOneWithFieldName(): void
    {
        $sql = 'SELECT title, albumid FROM albums WHERE (albumid between 2 and 3);';
        $values = $this->getDbal()->queryArrayOne($sql, 'albumid');
        $expectedValues = [2, 3];
        $this->assertEquals($expectedValues, $values);
    }

    public function testQueryArrayOneWithInvalidFieldName(): void
    {
        $values = $this->getDbal()->queryArrayOne('SELECT * FROM albums WHERE (albumid between 2 and 3);', 'foo');
        $this->assertSame(false, $values);
    }

    public function testQueryArrayOneWithError(): void
    {
        $this->assertSame(false, $this->getDbal()->queryArrayOne('SELECT * from nonexistent;'));
    }

    public function testQueryOnString(): void
    {
        $sql = 'SELECT albumid, title FROM albums WHERE (albumid between 1 and 3);';
        $value = $this->getDbal()->queryOnString($sql, 'default', ' * ');
        $expectedValue = '1 * 2 * 3';
        $this->assertSame($expectedValue, $value);
    }

    public function testQueryOnStringUsingDefaults(): void
    {
        $sql = 'SELECT albumid, title FROM albums WHERE (albumid between 1 and 3);';
        $value = $this->getDbal()->queryOnString($sql);
        $expectedValue = '1, 2, 3';
        $this->assertSame($expectedValue, $value);
    }

    public function testQueryOnStringWithError(): void
    {
        $sql = 'SELECT * FROM nonexistent';
        $expectedValue = 'default';
        $value = $this->getDbal()->queryOnString($sql, $expectedValue);
        $this->assertSame($expectedValue, $value);
    }

    /**
     * Override as needed
     * @return array<string, string>
     */
    public function overrideTypes(): array
    {
        return [];
    }

    // override if needed
    public function overrideEntity(): string
    {
        return '';
    }

    public function testExecuteWithError(): void
    {
        $expectedMessage = 'Invalid SQL Statement';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->getDbal()->execute('BAD STATEMENT;', $expectedMessage);
    }

    public function testQueryResult(): void
    {
        $expectedTablename = $this->overrideEntity();
        $sql = 'SELECT * FROM albums WHERE (albumid = 5);';
        $result = $this->queryResult($sql, $this->overrideTypes());
        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame(1, $result->resultCount());
        // get first
        $fetchedFirst = $result->fetchRow();
        $this->assertIsArray($fetchedFirst);
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

        $fields = $result->getFields();
        $this->assertEquals(array_replace_recursive($fields, $expectedFields), $fields);
    }

    public function testCreateRecordsetWithInvalidQueryCreatesException(): void
    {
        $query = 'select * from nonexistent';
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Unable to create a valid Recordset');
        $this->getDbal()->createRecordset($query);
    }

    public function testCreatePagerWithInvalidQueryCreatesException(): void
    {
        $query = 'select * from nonexistent';
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Unable to create a valid Pager');
        $this->getDbal()->createPager($query);
    }

    /** @return array<string, string[]> */
    public function providerDateFormat(): array
    {
        return [
            'year' => ['YEAR', '2021'],
            'month' => ['MONTH', '01'],
            'day' => ['DAY', '13'],
            'hour' => ['HOUR', '14'],
            'minute' => ['MINUTE', '15'],
            'second' => ['SECOND', '16'],
            'first day of month' => ['FDOM', '2021-01-01'],
            'year-month' => ['FYM', '2021-01'],
            'year-month-day' => ['FYMD', '2021-01-13'],
            'hour:minute:second' => ['FHMS', '14:15:16'],
        ];
    }

    /**
     * @param string $formatPart
     * @param string $expected
     * @dataProvider providerDateFormat
     */
    public function testDateFormat(string $formatPart, string $expected): void
    {
        $time = strtotime('2021-01-13 14:15:16');
        $dbal = $this->getDbal();
        $query = 'SELECT ' . $dbal->sqlDatePart($formatPart, $dbal->sqlQuote($time, $dbal::TDATETIME));
        $this->assertSame(
            $expected,
            (string) $dbal->queryOne($query),
            "sqlDatePart fail, query: $query"
        );
    }

    public function testSqlIsNullWithNegationTriggerNotice(): void
    {
        $dbal = $this->getDbal();
        $this->expectNotice();
        $dbal->sqlIsNull('foo', false);
    }

    public function testSqlInWithNegationTriggerNotice(): void
    {
        $dbal = $this->getDbal();
        $this->expectNotice();
        $dbal->sqlIn('foo', ['bar', 'baz'], CommonTypes::TTEXT, false);
    }

    public function testQueryTriggerDeprecation(): void
    {
        $dbal = $this->getDbal();
        $this->expectDeprecation();
        $dbal->query('SELECT 1');
    }
}
