<?php
namespace EngineWorks\DBAL\Tests;

abstract class SqliteWithDatabaseTestCase extends WithDatabaseTestCase
{
    protected function checkIsAvailable()
    {
        if (! class_exists('\SQLite3')) {
            $this->markTestSkipped('Environment does not have the extension sqlite3');
        }
    }

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
            . ' albumid INTEGER PRIMARY KEY NOT NULL,'
            . ' title NVARCHAR(160) NOT NULL,'
            . ' votes INTEGER NULL,'
            . ' lastview DATETIME NULL,'
            . ' isfree BOOLEAN NOT NULL,'
            . ' collect DECIMAL(12, 2) NOT NULL DEFAULT 0)'
            . ';',
        ]);
    }
}
