<?php
namespace EngineWorks\DBAL\Tests\Sqlite;

use EngineWorks\DBAL\Tests\TestCaseWithSqliteDatabase;

class DBALConnectedTest extends TestCaseWithSqliteDatabase
{
    public function testQueryOneWithValues()
    {
        $expected = 45;
        $value = $this->dbal->queryOne('SELECT COUNT(*) FROM albums;');

        $this->assertSame($expected, $value);
    }

    public function testQueryOneWithError()
    {
        $expected = -10;
        $value = $this->dbal->queryOne('SELECT NULL FROM albums WHERE (albumid = -1);', $expected);

        $this->assertSame($expected, $value);
    }
}
