<?php
namespace EngineWorks\DBAL\Tests\Mysqli;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Factory;
use EngineWorks\DBAL\Settings;
use EngineWorks\DBAL\Tests\Sample\ArrayLogger;
use EngineWorks\DBAL\Tests\SqlQuoteTester;
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

    /**
     * @param $locale
     * @param $expected
     * @param $value
     * @testWith ["C", "-1234.56789", "- $\t1,234.567,89 "]
     *           ["en_US", "-1234.56789", "- $\t1,234.567,89 "]
     *           ["en_US.utf-8", "-1234.56789", "- $\t1,234.567,89 "]
     *           ["pt_BR", "-1234.56789", "- R$\t1.234,567.89 ", "NUMBER"]
     */
    public function testSqlQuoteWithLocale($locale, $expected, $value)
    {
        $currentNumeric = setlocale(LC_NUMERIC, '0');
        $currentMonetary = setlocale(LC_MONETARY, '0');

        // mark skipped if not found
        if (false === setlocale(LC_NUMERIC, $locale) || false === setlocale(LC_MONETARY, $locale)) {
            setlocale(LC_NUMERIC, $currentNumeric);
            setlocale(LC_MONETARY, $currentMonetary);
            $this->markTestSkipped("Cannot setup locale '$locale'");
        }

        // the test
        $this->assertSame($expected, $this->dbal->sqlQuote($value, DBAL::TNUMBER, false));

        // anyhow restore the previous locale
        setlocale(LC_NUMERIC, $currentNumeric);
        setlocale(LC_MONETARY, $currentMonetary);
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

    public function testSqlQuoteUsingTester()
    {
        $tester = new SqlQuoteTester($this, $this->dbal, "'\\''", "'\\\"'");
        $tester->execute();
    }
}
