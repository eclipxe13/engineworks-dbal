<?php
namespace EngineWorks\DBAL\Tests\Sqlite;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Factory;
use EngineWorks\DBAL\Settings;
use EngineWorks\DBAL\Tests\Sample\ArrayLogger;
use EngineWorks\DBAL\Tests\SqlQuoteTester;
use PHPUnit\Framework\TestCase;

class SqliteDisconnectedTest extends TestCase
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
            $this->factory = new Factory('EngineWorks\DBAL\Sqlite');
            $this->settings = $this->factory->settings([
                'filename' => 'non-existent',
                'flags' => 0, // prevent to create
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
            'error: Cannot create SQLite3 object: Unable to open database: ',
        ];
        $messages = $logger->allMessages();
        $this->assertCount(count($expectedLogs), $messages);
        foreach ($messages as $index => $message) {
            $this->assertStringStartsWith($expectedLogs[$index], $message);
        }
    }

    /*
     *
     * sql tests
     *
     */

    public function testSqlField()
    {
        $dbal = $this->factory->dbal($this->factory->settings([]));
        $expectedName = 'some-field AS "some - label"';
        $this->assertSame($expectedName, $dbal->sqlField('some-field', 'some - label'));
    }

    public function testSqlFieldEscape()
    {
        $dbal = $this->factory->dbal($this->factory->settings([]));
        $expectedName = '"some-field" AS "some - label"';
        $this->assertSame($expectedName, $dbal->sqlFieldEscape('some-field', 'some - label'));
    }

    public function testSqlTable()
    {
        $dbal = $this->factory->dbal($this->factory->settings([
            'prefix' => 'foo_',
        ]));
        $expectedName = '"foo_bar" AS "x"';
        $this->assertSame($expectedName, $dbal->sqlTable('bar', 'x'));
        $expectedNoSuffix = '"bar" AS "x"';
        $this->assertSame($expectedNoSuffix, $dbal->sqlTableEscape('bar', 'x'));
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
        $this->assertSame('random()', $this->dbal->sqlRandomFunc());
    }

    public function testSqlIsNull()
    {
        $this->assertSame('foo IS NULL', $this->dbal->sqlIsNull('foo'));
        $this->assertSame('foo IS NOT NULL', $this->dbal->sqlIsNull('foo', false));
    }

    public function testSqlIf()
    {
        $this->assertSame(
            'CASE WHEN (condition) THEN true ELSE false',
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
        $this->assertSame('9 || 8 || 7', $this->dbal->sqlConcatenate(...['9', '8', '7']));
        $this->assertSame('a || b || c', $this->dbal->sqlConcatenate('a', 'b', 'c'));
        $this->assertSame("''", $this->dbal->sqlConcatenate());
    }

    public function testSqlQuoteUsingTester()
    {
        $tester = new SqlQuoteTester($this, $this->dbal);
        $tester->execute();
    }
}
