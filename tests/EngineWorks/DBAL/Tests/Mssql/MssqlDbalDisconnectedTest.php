<?php
namespace EngineWorks\DBAL\Tests\Mssql;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Factory;
use EngineWorks\DBAL\Settings;
use EngineWorks\DBAL\Tests\Sample\ArrayLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class MssqlDbalDisconnectedTest extends TestCase
{
    /** @var Factory */
    private $factory;

    /** @var DBAL */
    private $dbal;

    /** @var Settings */
    private $settings;

    protected function setUp()
    {
        parent::setUp();
        if ($this->dbal === null) {
            $this->factory = new Factory('EngineWorks\DBAL\Mssql');
            $this->settings = $this->factory->settings();
            $this->dbal = $this->factory->dbal($this->settings);
        }
    }

    /** @return ArrayLogger|\Psr\Log\LoggerInterface */
    protected function dbalGetArrayLogger()
    {
        return $this->dbal->getLogger();
    }

    protected function dbalSetArrayLogger()
    {
        $this->dbal->setLogger(new ArrayLogger());
    }

    protected function dbalUnsetArrayLogger()
    {
        $this->dbal->setLogger(new NullLogger());
    }

    /*
     *
     * connect & disconnect tests
     *
     */
    public function testConnectReturnFalseWhenCannotConnect()
    {
        $dbal = $this->factory->dbal($this->factory->settings([
        ]));
        $logger = new ArrayLogger();
        $dbal->setLogger($logger);
        $this->assertFalse($dbal->connect());
        $expectedLogs = [
            'info: -- Connection fail',
            'error: Cannot create',
        ];
        $messages = $logger->allMessages();
        $count = 0;
        foreach ($expectedLogs as $expectedLog) {
            $this->assertContains($expectedLog, $messages[$count]);
            $count = $count + 1;
        }
    }

    /*
     *
     * sql tests
     *
     */
    public function testSqlTable()
    {
        $dbal = $this->factory->dbal($this->factory->settings([
            'filename' => 'non-existent',
            'prefix' => 'foo_',

        ]));
        $expectedName = '[foo_bar] AS x';
        $this->assertSame($expectedName, $dbal->sqlTable('bar', 'x'));
    }

    public function providerSqlQuote()
    {
        $timestamp = mktime(23, 31, 59, 12, 31, 2016);
        $date = '2016-12-31';
        $time = '23:31:59';
        $datetime = "$date $time";
        return [
            // texts
            'text normal' => ["'foo'", 'foo', DBAL::TTEXT, false],
            'text normal include null' => ["'foo'", 'foo', DBAL::TTEXT, true],
            'text zero' => ["'0'", 0, DBAL::TTEXT, false],
            'text integer' => ["'9'", 9, DBAL::TTEXT, false],
            'text float' => ["'1.2'", 1.2, DBAL::TTEXT, false],
            // integer
            'integer normal' => ['9', 9, DBAL::TINT, false],
            'integer float' => ['1', 1.2, DBAL::TINT, false],
            'integer text not numeric' => ['0', 'foo bar', DBAL::TINT, false],
            'integer text numeric simple' => ['987', '987', DBAL::TINT, false],
            'integer text numeric complex' => ['-1234', '- $ 1,234.56', DBAL::TINT, false],
            'integer empty' => ['0', '', DBAL::TINT, false],
            'integer whitespace' => ['0', ' ', DBAL::TINT, false],
            // float
            'float normal' => ['9.1', 9.1, DBAL::TNUMBER, false],
            'float int' => ['8', 8, DBAL::TNUMBER, false],
            'float text not numeric' => ['0', 'foo bar', DBAL::TNUMBER, false],
            'float text numeric simple' => ['987.654', '987.654', DBAL::TNUMBER, false],
            'float text numeric complex' => ['-1234.56789', '- $ 1,234.567,89', DBAL::TNUMBER, false],
            'float empty' => ['0', '', DBAL::TNUMBER, false],
            'float whitespace' => ['0', ' ', DBAL::TNUMBER, false],
            // bool
            'bool normal false' => ['0', false, DBAL::TBOOL, false],
            'bool equal false' => ['0', '0', DBAL::TBOOL, false],
            'bool normal true' => ['1', true, DBAL::TBOOL, false],
            'bool equal true' => ['1', 'foo', DBAL::TBOOL, false],
            // date time datetime
            'date normal' => ["'$date'", $timestamp, DBAL::TDATE, false],
            'time normal' => ["'$time'", $timestamp, DBAL::TTIME, false],
            'datetime normal' => ["'$datetime'", $timestamp, DBAL::TDATETIME, false],
            //
            // nulls
            //
            'null text' => ['NULL', null, DBAL::TTEXT, true],
            'null int' => ['NULL', null, DBAL::TINT, true],
            'null float' => ['NULL', null, DBAL::TNUMBER, true],
            'null bool' => ['NULL', null, DBAL::TBOOL, true],
            'null date' => ['NULL', null, DBAL::TDATE, true],
            'null time' => ['NULL', null, DBAL::TTIME, true],
            'null datetime' => ['NULL', null, DBAL::TDATETIME, true],
            'null text notnull' => ["''", null, DBAL::TTEXT, false],
            'null int notnull' => ['0', null, DBAL::TINT, false],
            'null float notnull' => ['0', null, DBAL::TNUMBER, false],
            'null bool notnull' => ['0', null, DBAL::TBOOL, false],
            'null date notnull' => ["'1970-01-01'", null, DBAL::TDATE, false],
            'null time notnull' => ["'00:00:00'", null, DBAL::TTIME, false],
            'null datetime notnull' => ["'1970-01-01 00:00:00'", null, DBAL::TDATETIME, false],
            // special chars
            "special char '" => ["''''", "'", DBAL::TTEXT, false],
            'special char \"' => ["'\"'", '"', DBAL::TTEXT, false],
        ];
    }

    /**
     * @param $expected
     * @param $value
     * @param $type
     * @param $includeNull
     * @dataProvider providerSqlQuote
     */
    public function testSqlQuote($expected, $value, $type, $includeNull)
    {
        $this->assertSame($expected, $this->dbal->sqlQuote($value, $type, $includeNull));
    }

    public function testSqlQuoteIn()
    {
        $expected = '(1, 2, 3, 4, 5)';
        $this->assertSame($expected, $this->dbal->sqlQuoteIn(range(1, 5), DBAL::TINT));
        $this->assertSame(false, $this->dbal->sqlQuoteIn([], DBAL::TINT));
    }

    public function testSqlString()
    {
        $this->assertSame("  foo\tbar  \n", $this->dbal->sqlString("  foo\tbar  \n"));
        $this->assertSame("''", $this->dbal->sqlString("'"));
        $this->assertSame('ab', $this->dbal->sqlString("a\0b"));
        $this->assertSame('\\', $this->dbal->sqlString('\\'));
        $this->assertSame("''''''", $this->dbal->sqlString("'''"));
    }

    public function testSqlRandomFunc()
    {
        $this->assertSame('RAND()', $this->dbal->sqlRandomFunc());
    }

    public function testSqlIsNull()
    {
        $this->assertSame('foo IS NULL', $this->dbal->sqlIsNull('foo'));
        $this->assertSame('foo IS NOT NULL', $this->dbal->sqlIsNull('foo', false));
    }

    public function testSqlIf()
    {
        $this->assertSame(
            'CASE WHEN (condition) THEN true ELSE false END',
            $this->dbal->sqlIf('condition', 'true', 'false')
        );
    }

    public function testSqlIfNull()
    {
        $this->assertSame('IFNULL(foo, bar)', $this->dbal->sqlIfNull('foo', 'bar'));
    }

    public function testSqlLimit()
    {
        $expected = 'SELECT a OFFSET 80 ROWS FETCH NEXT 20 ROWS ONLY;';
        $this->assertSame($expected, $this->dbal->sqlLimit('SELECT a ', 5, 20));
        $this->assertSame($expected, $this->dbal->sqlLimit('SELECT a;', 5, 20));
    }

    public function testSqlLike()
    {
        $this->assertSame("field LIKE '%search%'", $this->dbal->sqlLike('field', 'search'));
        $this->assertSame("field LIKE 'search%'", $this->dbal->sqlLike('field', 'search', false));
        $this->assertSame("field LIKE 'search'", $this->dbal->sqlLike('field', 'search', false, false));
        $this->assertSame("field LIKE '%search'", $this->dbal->sqlLike('field', 'search', true, false));
    }

    public function testSqlLikeSearch()
    {
        // regular
        $expected = "(foo LIKE '%bar%') OR (foo LIKE '%baz%')";
        $this->assertSame($expected, $this->dbal->sqlLikeSearch('foo', 'bar  baz'));
        // all words
        $expected = "(foo LIKE '%bar%') AND (foo LIKE '%baz%')";
        $this->assertSame($expected, $this->dbal->sqlLikeSearch('foo', 'bar  baz', false));
        // change separator
        $expected = "(foo LIKE '%bar%') OR (foo LIKE '%baz%')";
        $this->assertSame($expected, $this->dbal->sqlLikeSearch('foo', 'bar;;baz', true, ';'));
        // empty or invalid strings
        $this->assertSame('', $this->dbal->sqlLikeSearch('foo', ''));
        $this->assertSame('', $this->dbal->sqlLikeSearch('foo', new \stdClass()));
    }

    public function testSqlConcatenate()
    {
        $this->assertSame('CONCAT(9, 8, 7)', $this->dbal->sqlConcatenate(...['9', '8', '7']));
        $this->assertSame('CONCAT(a, b, c)', $this->dbal->sqlConcatenate('a', 'b', 'c'));
        $this->assertSame("''", $this->dbal->sqlConcatenate());
    }
}
