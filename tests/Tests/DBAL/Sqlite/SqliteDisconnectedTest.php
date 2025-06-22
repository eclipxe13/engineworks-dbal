<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\Sqlite;

use EngineWorks\DBAL\Tests\DBAL\TesterCases\SqlQuoteTester;
use EngineWorks\DBAL\Tests\DBAL\TesterTraits\DbalCommonSqlTrait;
use EngineWorks\DBAL\Tests\DBAL\TesterTraits\DbalLoggerTrait;
use EngineWorks\DBAL\Tests\WithDbalTestCase;

class SqliteDisconnectedTest extends WithDbalTestCase
{
    use DbalCommonSqlTrait;
    use DbalLoggerTrait;

    protected function getFactoryNamespace(): string
    {
        return 'EngineWorks\DBAL\Sqlite';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDbalWithSettings([
            'filename' => 'non-existent',
            'flags' => 0, // prevent to create
        ]);
    }

    public function testConnectReturnFalseWhenCannotConnect(): void
    {
        $this->assertFalse($this->getDbal()->connect());
        $expectedLogs = [
            'info: -- Connection fail',
            'error: Cannot create SQLite3 object: Unable to open database: ',
        ];
        $messages = $this->getLogger()->allMessages();
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

    public function testSqlField(): void
    {
        $expectedName = 'some-field AS "some - label"';
        $this->assertSame($expectedName, $this->getDbal()->sqlField('some-field', 'some - label'));
    }

    public function testSqlFieldEscape(): void
    {
        $expectedName = '"some-field" AS "some - label"';
        $this->assertSame($expectedName, $this->getDbal()->sqlFieldEscape('some-field', 'some - label'));
    }

    public function testSqlTable(): void
    {
        $this->setupDbalWithSettings([
            'prefix' => 'foo_',
        ]);
        $db = $this->getDbal();
        $expectedName = '"foo_bar" AS "x"';
        $this->assertSame($expectedName, $db->sqlTable('bar', 'x'));
        $expectedNoSuffix = '"bar" AS "x"';
        $this->assertSame($expectedNoSuffix, $db->sqlTableEscape('bar', 'x'));
    }

    public function testSqlString(): void
    {
        $db = $this->getDbal();
        $this->assertSame("  foo\tbar  \n", $db->sqlString("  foo\tbar  \n"));
        $this->assertSame("''", $db->sqlString("'"));
        $this->assertSame('ab', $db->sqlString("a\0b"));
        $this->assertSame('\\', $db->sqlString('\\'));
        $this->assertSame("''''''", $db->sqlString("'''"));
    }

    public function testSqlRandomFunc(): void
    {
        $this->assertSame('random()', $this->getDbal()->sqlRandomFunc());
    }

    public function testSqlIf(): void
    {
        $this->assertSame(
            'CASE WHEN (condition) THEN true ELSE false',
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
        $this->assertSame('9 || 8 || 7', $db->sqlConcatenate(...['9', '8', '7']));
        $this->assertSame('a || b || c', $db->sqlConcatenate('a', 'b', 'c'));
        $this->assertSame("''", $db->sqlConcatenate());
    }

    public function testSqlQuoteUsingTester(): void
    {
        $tester = new SqlQuoteTester($this);
        $tester->execute();
    }
}
