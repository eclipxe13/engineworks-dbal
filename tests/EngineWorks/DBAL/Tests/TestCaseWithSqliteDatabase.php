<?php
namespace EngineWorks\DBAL\Tests;

abstract class TestCaseWithSqliteDatabase extends TestCaseWithDatabase
{
    protected function getFactoryNamespace()
    {
        return 'EngineWorks\DBAL\Sqlite';
    }

    protected function getSettingsArray()
    {
        return [
            'filename' => ':memory:',
        ];
    }

    protected function createDatabaseStructure()
    {
        $this->executeStatements([
            'CREATE ' . ' TABLE albums ('
            . ' albumid INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,'
            . ' title NVARCHAR(160) NOT NULL,'
            . ' votes INTEGER NULL,'
            . ' lastview DATETIME NULL,'
            . ' isfree BOOLEAN NOT NULL,'
            . ' collect DECIMAL(12, 2) NOT NULL DEFAULT 0)'
            . ';',
        ]);
    }
}
