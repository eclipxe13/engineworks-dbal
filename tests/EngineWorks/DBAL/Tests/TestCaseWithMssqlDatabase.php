<?php
namespace EngineWorks\DBAL\Tests;

class TestCaseWithMssqlDatabase extends TestCaseWithDatabase
{
    protected function setUp()
    {
        if (! function_exists('mssql_connect')) {
            $this->markTestSkipped('Environment does not have the extension mssql');
        }
        parent::setUp();
    }

    protected function getFactoryNamespace()
    {
        return 'EngineWorks\DBAL\Mssql';
    }

    protected function getSettingsArray()
    {
        return [
            'host' => 'localhost',
            'port' => '9433',
            'database' => '',
            'user' => 'sa',
            'password' => '',
            'encoding' => '',
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
            . ' albumid INTEGER IDENTITY(1,1) PRIMARY KEY,'
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
