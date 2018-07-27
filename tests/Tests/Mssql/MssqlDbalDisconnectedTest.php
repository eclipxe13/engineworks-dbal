<?php
namespace EngineWorks\DBAL\Tests\Mssql;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Factory;
use EngineWorks\DBAL\Settings;
use EngineWorks\DBAL\Tests\DbalCommonSqlTrait;
use EngineWorks\DBAL\Tests\Sample\ArrayLogger;
use EngineWorks\DBAL\Tests\SqlQuoteTester;
use PHPUnit\Framework\TestCase;

class MssqlDbalDisconnectedTest extends TestCase
{
    use DbalCommonSqlTrait;

    /** @var Factory */
    private $factory;

    /** @var DBAL */
    private $dbal;

    /** @var Settings */
    private $settings;

    protected function getDbal(): DBAL
    {
        return $this->dbal;
    }

    protected function setUp()
    {
        parent::setUp();
        if ($this->dbal === null) {
            $this->factory = new Factory('EngineWorks\DBAL\Mssql');
            $this->settings = $this->factory->settings();
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
        $dbal = $this->factory->dbal($this->factory->settings([]));
        $expectedName = 'some-field AS [some - label]';
        $this->assertSame($expectedName, $dbal->sqlField('some-field', 'some - label'));
    }

    public function testSqlFieldEscape()
    {
        $dbal = $this->factory->dbal($this->factory->settings([]));
        $expectedName = '[some-field] AS [some - label]';
        $this->assertSame($expectedName, $dbal->sqlFieldEscape('some-field', 'some - label'));
    }

    public function testSqlTable()
    {
        $dbal = $this->factory->dbal($this->factory->settings([
            'prefix' => 'foo_',
        ]));
        $expectedName = '[foo_bar] AS [x]';
        $this->assertSame($expectedName, $dbal->sqlTable('bar', 'x'));
        $expectedNoSuffix = '[bar] AS [x]';
        $this->assertSame($expectedNoSuffix, $dbal->sqlTableEscape('bar', 'x'));
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

    public function testSqlConcatenate()
    {
        $this->assertSame('CONCAT(9, 8, 7)', $this->dbal->sqlConcatenate(...['9', '8', '7']));
        $this->assertSame('CONCAT(a, b, c)', $this->dbal->sqlConcatenate('a', 'b', 'c'));
        $this->assertSame("''", $this->dbal->sqlConcatenate());
    }

    public function testSqlQuoteUsingTester()
    {
        $tester = new SqlQuoteTester($this, $this->dbal);
        $tester->execute();
    }
}
