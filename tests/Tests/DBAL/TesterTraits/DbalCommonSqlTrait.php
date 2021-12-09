<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\TesterTraits;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Tests\WithDbalTestCase;
use RuntimeException;

/** @var WithDbalTestCase $this */
trait DbalCommonSqlTrait
{
    abstract public function getDbal(): DBAL;

    public function testSqlLike(): void
    {
        $dbal = $this->getDbal();
        $this->assertSame("field LIKE '%search%'", $dbal->sqlLike('field', 'search'));
        $this->assertSame("field LIKE 'search%'", $dbal->sqlLike('field', 'search', false));
        $this->assertSame("field LIKE 'search'", $dbal->sqlLike('field', 'search', false, false));
        $this->assertSame("field LIKE '%search'", $dbal->sqlLike('field', 'search', true, false));
    }

    public function testSqlLikeSearch(): void
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

    public function testSqlQuoteIn(): void
    {
        $dbal = $this->getDbal();
        $expected = '(1, 2, 3, 4, 5)';
        $this->assertSame($expected, $dbal->sqlQuoteIn([1, 2, 3, 4, 5], DBAL::TINT));
    }

    public function testSqlQuoteInWithRepeatedValues(): void
    {
        $dbal = $this->getDbal();
        $expected = '(1)';
        $this->assertSame($expected, $dbal->sqlQuoteIn([1, 1, 1, 1, 1], DBAL::TINT));
    }

    public function testSqlQuoteInWithEmptyArray(): void
    {
        $dbal = $this->getDbal();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The array of values passed to DBAL::sqlQuoteIn is empty');
        $dbal->sqlQuoteIn([]);
    }

    public function testSqlIsNull(): void
    {
        $dbal = $this->getDbal();
        $this->assertSame('foo IS NULL', $dbal->sqlIsNull('foo'));
        $this->assertSame('foo IS NOT NULL', $dbal->sqlIsNotNull('foo'));
    }

    public function testsqlBetweenQuote(): void
    {
        $dbal = $this->getDbal();
        $this->assertSame(
            sprintf('xe BETWEEN %s AND %s', $dbal->sqlQuote(1, $dbal::TDATETIME), $dbal->sqlQuote(2, $dbal::TDATETIME)),
            $dbal->sqlBetweenQuote('xe', 1, 2, $dbal::TDATETIME)
        );
    }

    public function testSqlIfNull(): void
    {
        $dbal = $this->getDbal();
        $this->assertSame('IFNULL(foo, bar)', $dbal->sqlIfNull('foo', 'bar'));
    }

    public function testSqlIn(): void
    {
        $dbal = $this->getDbal();
        $expected = 'foo IN (3, 6, 9)';
        $this->assertSame($expected, $dbal->sqlIn('foo', [3, 6, 9], DBAL::TINT));
    }

    public function testSqlInWithEmptyArray(): void
    {
        $dbal = $this->getDbal();
        $expected = '0 = 1';
        $this->assertSame($expected, $dbal->sqlIn('foo', []));
    }

    public function testSqlNotIn(): void
    {
        $dbal = $this->getDbal();
        $expected = 'foo NOT IN (3, 6, 9)';
        $this->assertSame($expected, $dbal->sqlNotIn('foo', [3, 6, 9], DBAL::TINT));
    }

    public function testSqlNotInWithEmptyArray(): void
    {
        $dbal = $this->getDbal();
        $expected = '1 = 1';
        $this->assertSame($expected, $dbal->sqlNotIn('foo', []));
    }
}
