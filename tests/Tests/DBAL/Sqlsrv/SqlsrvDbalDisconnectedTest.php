<?php
namespace EngineWorks\DBAL\Tests\DBAL\Sqlsrv;

use EngineWorks\DBAL\Tests\DBAL\TesterCases\SqlQuoteTester;
use EngineWorks\DBAL\Tests\DBAL\TesterTraits\DbalCommonSqlTrait;
use EngineWorks\DBAL\Tests\WithDbalTestCase;

class SqlsrvDbalDisconnectedTest extends WithDbalTestCase
{
    use DbalCommonSqlTrait;

    protected function getFactoryNamespace()
    {
        return 'EngineWorks\DBAL\Sqlsrv';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDbalWithSettings([]);
    }

    /*
     *
     * sql tests
     *
     */

    public function testSqlField()
    {
        $expectedName = 'some-field AS [some - label]';
        $this->assertSame($expectedName, $this->dbal->sqlField('some-field', 'some - label'));
    }

    public function testSqlFieldEscape()
    {
        $expectedName = '[some-field] AS [some - label]';
        $this->assertSame($expectedName, $this->dbal->sqlFieldEscape('some-field', 'some - label'));
    }

    public function testSqlTable()
    {
        $this->setupDbalWithSettings([
            'prefix' => 'foo_',
        ]);
        $expectedName = '[foo_bar] AS [x]';
        $this->assertSame($expectedName, $this->dbal->sqlTable('bar', 'x'));
        $expectedNoSuffix = '[bar] AS [x]';
        $this->assertSame($expectedNoSuffix, $this->dbal->sqlTableEscape('bar', 'x'));
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

    public function testSqlIf()
    {
        $this->assertSame(
            'CASE WHEN (condition) THEN true ELSE false END',
            $this->dbal->sqlIf('condition', 'true', 'false')
        );
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
        $tester = new SqlQuoteTester($this);
        $tester->execute();
    }
}
