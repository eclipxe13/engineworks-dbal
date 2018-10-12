<?php
namespace EngineWorks\DBAL\Tests\DBAL\TesterTraits;

use EngineWorks\DBAL\DBAL;

trait DbalCommonSqlTrait
{
    abstract public function getDbal(): DBAL;

    public function testSqlLike()
    {
        $dbal = $this->getDbal();
        $this->assertSame("field LIKE '%search%'", $dbal->sqlLike('field', 'search'));
        $this->assertSame("field LIKE 'search%'", $dbal->sqlLike('field', 'search', false));
        $this->assertSame("field LIKE 'search'", $dbal->sqlLike('field', 'search', false, false));
        $this->assertSame("field LIKE '%search'", $dbal->sqlLike('field', 'search', true, false));
    }

    public function testSqlLikeSearch()
    {
        // regular
        $expected = "(foo LIKE '%bar%') OR (foo LIKE '%baz%')";
        $dbal = $this->getDbal();

        $this->assertSame($expected, $dbal->sqlLikeSearch('foo', 'bar  baz'));
        // repeated
        $expected = "(foo LIKE '%bar%') OR (foo LIKE '%baz%')";
        $this->assertSame($expected, $dbal->sqlLikeSearch('foo', 'bar  baz bar'));
        // all words
        $expected = "(foo LIKE '%bar%') AND (foo LIKE '%baz%')";
        $this->assertSame($expected, $dbal->sqlLikeSearch('foo', 'bar  baz', false));
        // change separator
        $expected = "(foo LIKE '%bar%') OR (foo LIKE '%baz%')";
        $this->assertSame($expected, $dbal->sqlLikeSearch('foo', 'bar;;baz', true, ';'));
        // empty or invalid strings
        $this->assertSame('', $dbal->sqlLikeSearch('foo', ''));
    }

    public function testSqlQuoteIn()
    {
        $dbal = $this->getDbal();
        $expected = '(1, 2, 3, 4, 5)';
        $this->assertSame($expected, $dbal->sqlQuoteIn([1, 2, 3, 4, 5], DBAL::TINT));
    }

    public function testSqlQuoteInWithRepeatedValues()
    {
        $dbal = $this->getDbal();
        $expected = '(1)';
        $this->assertSame($expected, $dbal->sqlQuoteIn([1, 1, 1, 1, 1], DBAL::TINT));
    }

    public function testSqlQuoteInWithEmptyArray()
    {
        $dbal = $this->getDbal();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The array of values passed to DBAL::sqlQuoteIn is empty');
        $dbal->sqlQuoteIn([]);
    }

    public function testSqlIsNull()
    {
        $dbal = $this->getDbal();
        $this->assertSame('foo IS NULL', $dbal->sqlIsNull('foo'));
    }

    public function testSqlIfNull()
    {
        $dbal = $this->getDbal();
        $this->assertSame('IFNULL(foo, bar)', $dbal->sqlIfNull('foo', 'bar'));
    }

    public function testSqlIn()
    {
        $dbal = $this->getDbal();
        $expected = 'foo NOT IN (3, 6, 9)';
        $this->assertSame($expected, $dbal->sqlIn('foo', [3, 6, 9], DBAL::TINT, false));
    }

    public function testSqlInWithEmptyArray()
    {
        $dbal = $this->getDbal();
        $expected = '0 = 1';
        $this->assertSame($expected, $dbal->sqlIn('foo', []));
    }
}
