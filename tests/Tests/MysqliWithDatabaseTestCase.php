<?php
namespace EngineWorks\DBAL\Tests;

class MysqliWithDatabaseTestCase extends WithDatabaseTestCase
{
    protected function checkIsAvailable()
    {
        if (getenv('testMysqli') !== 'yes') {
            $this->markTestSkipped('Environment does not include mysqli tests');
        }
        if (! function_exists('mysqli_init')) {
            $this->markTestSkipped('Environment does not have the extension mysqli');
        }
    }

    protected function getFactoryNamespace()
    {
        return 'EngineWorks\DBAL\Mysqli';
    }

    protected function getSettingsArray()
    {
        return [
            'host' => getenv('testMysqli_server'),
            'port' => getenv('testMysqli_port'),
            'database' => '',
            'user' => getenv('testMysqli_username'),
            'password' => getenv('testMysqli_password'),
        ];
    }

    protected function createDatabaseStructure()
    {
        $statements = [
            'DROP DATABASE IF EXISTS dbaltest;',
            'CREATE DATABASE dbaltest;',
            'USE dbaltest;',
            'CREATE TEMPORARY ' . ' TABLE albums ('
            . ' albumid INTEGER PRIMARY KEY NOT NULL,'
            . ' title NVARCHAR(160) NOT NULL,'
            . ' votes INTEGER NULL,'
            . ' lastview DATETIME NULL,'
            . ' isfree BOOL NOT NULL,'
            . ' collect DECIMAL(12, 2) NOT NULL DEFAULT 0)'
            . ';',
        ];
        $this->executeStatements($statements);
    }
}
