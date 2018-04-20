<?php
namespace EngineWorks\DBAL\Tests\Mysqli;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Factory;
use EngineWorks\DBAL\Settings;
use EngineWorks\DBAL\Tests\Sample\ArrayLogger;
use PHPUnit\Framework\TestCase;

class MysqliDisconnectedTest extends TestCase
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
            $this->factory = new Factory('EngineWorks\DBAL\Mysqli');
            $this->settings = $this->factory->settings([
                'user' => 'non-existent',
            ]);
            $this->dbal = $this->factory->dbal($this->settings);
        }
    }

    public function testConnectReturnFalseWhenCannotConnect()
    {
        $logger = new ArrayLogger();
        $this->dbal->setLogger($logger);
        $this->assertFalse($this->dbal->connect());
        $expectedLogs = [
            'info: -- Connection fail',
            'error: ',
        ];
        $expectedLogsCount = count($expectedLogs);
        $actualLogs = $logger->allMessages();
        for ($i = 0; $i < $expectedLogsCount; $i++) {
            $this->assertStringStartsWith($expectedLogs[$i], $actualLogs[$i]);
        }
    }

    /*
     *
     * sql tests
     *
     */

    public function testSqlField()
    {
        $expectedName = 'some-field AS `some - label`';
        $this->assertSame($expectedName, $this->dbal->sqlField('some-field', 'some - label'));
    }

    public function testSqlFieldEscape()
    {
        $expectedName = '`some-field` AS `some - label`';
        $this->assertSame($expectedName, $this->dbal->sqlFieldEscape('some-field', 'some - label'));
    }

    public function testSqlTable()
    {
        $dbal = $this->factory->dbal($this->factory->settings([
            'prefix' => 'foo_',
        ]));
        $expectedName = '`foo_bar` AS `x`';
        $this->assertSame($expectedName, $dbal->sqlTable('bar', 'x'));
        $expectedNoSuffix = '`bar` AS `x`';
        $this->assertSame($expectedNoSuffix, $dbal->sqlTableEscape('bar', 'x'));
    }

    public function providerSqlQuote()
    {
        $timestamp = mktime(23, 31, 59, 12, 31, 2016);
        $date = '2016-12-31';
        $time = '23:31:59';
        $datetime = "$date $time";
        $xmlValue = (new \SimpleXMLElement('<' . 'd v="55.1"/>'))['v'];
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
            'integer bool false' => ['0', false, DBAL::TINT, false],
            'integer bool true' => ['1', true, DBAL::TINT, false],
            // float
            'float normal' => ['9.1', 9.1, DBAL::TNUMBER, false],
            'float int' => ['8', 8, DBAL::TNUMBER, false],
            'float text not numeric' => ['0', 'foo bar', DBAL::TNUMBER, false],
            'float text numeric simple' => ['987.654', '987.654', DBAL::TNUMBER, false],
            'float text numeric complex' => ['-1234.56789', "- $\t1,234.567,89 ", DBAL::TNUMBER, false],
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
            //
            // object
            //
            'object to string' => ["'55.1'", $xmlValue, DBAL::TTEXT, true],
            'object to string not null' => ["'55.1'", $xmlValue, DBAL::TTEXT, false],
            'object to int' => ['55', $xmlValue, DBAL::TINT, true],
            'object to int not null' => ['55', $xmlValue, DBAL::TINT, false],
            'object to number' => ['55.1', $xmlValue, DBAL::TNUMBER, true],
            'object to number not null' => ['55.1', $xmlValue, DBAL::TNUMBER, false],
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
        $this->assertSame("  foo\tbar  \\n", $this->dbal->sqlString("  foo\tbar  \n"));
        $this->assertSame("\\'", $this->dbal->sqlString("'"));
        $this->assertSame('a\\0b', $this->dbal->sqlString("a\0b"));
        $this->assertSame('\\\\', $this->dbal->sqlString('\\'));
        $this->assertSame("\\'\\'\\'", $this->dbal->sqlString("'''"));
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
            'IF(condition, true, false)',
            $this->dbal->sqlIf('condition', 'true', 'false')
        );
    }

    public function testSqlIfNull()
    {
        $this->assertSame('IFNULL(foo, bar)', $this->dbal->sqlIfNull('foo', 'bar'));
    }

    public function testSqlLimit()
    {
        $expected = 'SELECT a LIMIT 20 OFFSET 80;';
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
