<?php
namespace EngineWorks\DBAL\Tests;

class TestCaseWithMssqlDatabase extends TestCaseWithDatabase
{
    protected function checkIsAvailable()
    {
        if (getenv('testMssql') !== 'yes') {
            $this->markTestSkipped('Environment does not include mssql tests');
        }
        if (! function_exists('pdo_drivers')) {
            $this->markTestSkipped('Environment does not have the extension pdo');
        }
        if (! in_array('dblib', pdo_drivers())) {
            $this->markTestSkipped('Environment does not have the extension pdo-dblib');
        }
    }

    protected function getFactoryNamespace()
    {
        return 'EngineWorks\DBAL\Mssql';
    }

    protected function getSettingsArray()
    {
        return [
            'host' => getenv('testMssql_server'),
            'port' => getenv('testMssql_port'),
            'database' => '',
            'user' => getenv('testMssql_username'),
            'password' => getenv('testMssql_password'),
            'encoding' => '',
            'connect-timeout' => 5,
        ];
    }

    protected function createDatabaseStructure()
    {
        $statements = [
            'USE master;',
            "IF EXISTS (SELECT * FROM sys.databases WHERE name = 'dbaltest') DROP DATABASE dbaltest;",
            'CREATE DATABASE dbaltest;',
            'USE dbaltest;',
            'CREATE ' . ' TABLE albums ('
            . ' albumid INTEGER PRIMARY KEY NOT NULL,'
            . ' title NVARCHAR(160) NOT NULL,'
            . ' votes INTEGER NULL,'
            . ' lastview DATETIME NULL,'
            . ' isfree BIT NOT NULL,'
            . ' collect DECIMAL(12, 2) NOT NULL DEFAULT 0)'
            . ';',
        ];
        $this->executeStatements($statements);
    }
}
