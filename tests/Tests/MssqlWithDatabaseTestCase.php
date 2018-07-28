<?php
namespace EngineWorks\DBAL\Tests;

class MssqlWithDatabaseTestCase extends WithDatabaseTestCase
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
            'connect-timeout' => getenv('testMssql_connect_timeout'),
            'freetds-version' => getenv('testMssql_freetds_version'),
        ];
    }

    protected function createDatabaseStructure()
    {
        /*
         * These statements were used to drop the database but in new version is very expensive,
         * now is using pre-existent empty database tempdb and only drops table if exists
         *
         * These are the old statements to drop & create the database:
         * 'USE master;',
         * "IF EXISTS (SELECT * FROM sys.databases WHERE name = 'dbaltest') DROP DATABASE dbaltest;",
         * 'CREATE DATABASE dbaltest;',
         * 'USE dbaltest;',
         *
         */
        $statements = [
            'USE tempdb;',
            "IF EXISTS (SELECT * FROM information_schema.tables WHERE table_name = 'albums') DROP TABLE albums;",
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
