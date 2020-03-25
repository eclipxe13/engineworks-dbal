<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\Sqlite;

use EngineWorks\DBAL\Tests\DBAL\Sample\ArrayLogger;
use EngineWorks\DBAL\Tests\DBAL\TesterCases\SqlQuoteTester;
use EngineWorks\DBAL\Tests\DBAL\TesterTraits\DbalCommonSqlTrait;
use EngineWorks\DBAL\Tests\WithDbalTestCase;

class SqliteDisconnectedTest extends WithDbalTestCase
{
    use DbalCommonSqlTrait;

    protected function getFactoryNamespace()
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

    public function testSqlField(): void
    {
        $expectedName = 'some-field AS "some - label"';
        $this->assertSame($expectedName, $this->dbal->sqlField('some-field', 'some - label'));
    }

    public function testSqlFieldEscape(): void
    {
        $expectedName = '"some-field" AS "some - label"';
        $this->assertSame($expectedName, $this->dbal->sqlFieldEscape('some-field', 'some - label'));
    }

    public function testSqlTable(): void
    {
        $this->setupDbalWithSettings([
            'prefix' => 'foo_',
        ]);
        $expectedName = '"foo_bar" AS "x"';
        $this->assertSame($expectedName, $this->dbal->sqlTable('bar', 'x'));
        $expectedNoSuffix = '"bar" AS "x"';
        $this->assertSame($expectedNoSuffix, $this->dbal->sqlTableEscape('bar', 'x'));
    }

    public function testSqlString(): void
    {
        $this->assertSame("  foo\tbar  \n", $this->dbal->sqlString("  foo\tbar  \n"));
        $this->assertSame("''", $this->dbal->sqlString("'"));
        $this->assertSame('ab', $this->dbal->sqlString("a\0b"));
        $this->assertSame('\\', $this->dbal->sqlString('\\'));
        $this->assertSame("''''''", $this->dbal->sqlString("'''"));
    }

    public function testSqlRandomFunc(): void
    {
        $this->assertSame('random()', $this->dbal->sqlRandomFunc());
    }

    public function testSqlIf(): void
    {
        $this->assertSame(
            'CASE WHEN (condition) THEN true ELSE false',
            $this->dbal->sqlIf('condition', 'true', 'false')
        );
    }

    public function testSqlLimit(): void
    {
        $expected = 'SELECT a LIMIT 20 OFFSET 80;';
        $this->assertSame($expected, $this->dbal->sqlLimit('SELECT a ', 5, 20));
        $this->assertSame($expected, $this->dbal->sqlLimit('SELECT a;', 5, 20));
    }

    public function testSqlConcatenate(): void
    {
        $this->assertSame('9 || 8 || 7', $this->dbal->sqlConcatenate(...['9', '8', '7']));
        $this->assertSame('a || b || c', $this->dbal->sqlConcatenate('a', 'b', 'c'));
        $this->assertSame("''", $this->dbal->sqlConcatenate());
    }

    public function testSqlQuoteUsingTester(): void
    {
        $tester = new SqlQuoteTester($this);
        $tester->execute();
    }
}
