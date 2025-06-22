<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\Mysqli;

use EngineWorks\DBAL\Tests\DBAL\TesterCases\SqlQuoteTester;
use EngineWorks\DBAL\Tests\DBAL\TesterTraits\DbalCommonSqlTrait;
use EngineWorks\DBAL\Tests\DBAL\TesterTraits\DbalLoggerTrait;
use EngineWorks\DBAL\Tests\WithDbalTestCase;

class MysqliDisconnectedTest extends WithDbalTestCase
{
    use DbalCommonSqlTrait;
    use DbalLoggerTrait;

    protected function getFactoryNamespace(): string
    {
        return 'EngineWorks\DBAL\Mysqli';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDbalWithSettings([
            'user' => 'non-existent',
        ]);
    }

    public function testConnectReturnFalseWhenCannotConnect(): void
    {
        $this->assertFalse($this->getDbal()->connect());
        $expectedLogs = [
            'info: -- Connection fail',
            'error: ',
        ];
        $actualLogs = $this->getLogger()->allMessages();
        foreach ($expectedLogs as $i => $expectedLog) {
            $this->assertStringStartsWith($expectedLog, $actualLogs[$i]);
        }
    }

    /*
     *
     * sql tests
     *
     */

    public function testSqlField(): void
    {
        $expectedName = 'some-field AS `some - label`';
        $this->assertSame($expectedName, $this->getDbal()->sqlField('some-field', 'some - label'));
    }

    public function testSqlFieldEscape(): void
    {
        $expectedName = '`some-field` AS `some - label`';
        $this->assertSame($expectedName, $this->getDbal()->sqlFieldEscape('some-field', 'some - label'));
    }

    public function testSqlTable(): void
    {
        $this->setupDbalWithSettings([
            'prefix' => 'foo_',
        ]);
        $db = $this->getDbal();
        $expectedName = '`foo_bar` AS `x`';
        $this->assertSame($expectedName, $db->sqlTable('bar', 'x'));
        $expectedNoSuffix = '`bar` AS `x`';
        $this->assertSame($expectedNoSuffix, $db->sqlTableEscape('bar', 'x'));
    }

    public function testSqlString(): void
    {
        $db = $this->getDbal();
        $this->assertSame("  foo\tbar  \\n", $db->sqlString("  foo\tbar  \n"));
        $this->assertSame("\\'", $db->sqlString("'"));
        $this->assertSame('a\\0b', $db->sqlString("a\0b"));
        $this->assertSame('\\\\', $db->sqlString('\\'));
        $this->assertSame("\\'\\'\\'", $db->sqlString("'''"));
    }

    public function testSqlRandomFunc(): void
    {
        $this->assertSame('RAND()', $this->getDbal()->sqlRandomFunc());
    }

    public function testSqlIf(): void
    {
        $this->assertSame(
            'IF(condition, true, false)',
            $this->getDbal()->sqlIf('condition', 'true', 'false')
        );
    }

    public function testSqlLimit(): void
    {
        $db = $this->getDbal();
        $expected = 'SELECT a LIMIT 20 OFFSET 80;';
        $this->assertSame($expected, $db->sqlLimit('SELECT a ', 5, 20));
        $this->assertSame($expected, $db->sqlLimit('SELECT a;', 5, 20));
    }

    public function testSqlConcatenate(): void
    {
        $db = $this->getDbal();
        $this->assertSame('CONCAT(9, 8, 7)', $db->sqlConcatenate(...['9', '8', '7']));
        $this->assertSame('CONCAT(a, b, c)', $db->sqlConcatenate('a', 'b', 'c'));
        $this->assertSame("''", $db->sqlConcatenate());
    }

    public function testSqlQuoteUsingTester(): void
    {
        $tester = new SqlQuoteTester($this, "'\\''", "'\\\"'");
        $tester->execute();
    }
}
