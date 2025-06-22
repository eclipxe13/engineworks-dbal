<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests;

use mysqli_driver;

class MysqliWithDatabaseTestCase extends WithDatabaseTestCase
{
    protected function checkIsAvailable(): void
    {
        if ('yes' !== $this->getenv('testMysqli')) {
            $this->markTestSkipped('Environment does not include mysqli tests');
        }
        if (! function_exists('mysqli_init')) {
            $this->markTestSkipped('Environment does not have the extension mysqli');
        }
        if (MYSQLI_REPORT_OFF !== (new mysqli_driver())->report_mode) {
            if (! mysqli_report(MYSQLI_REPORT_OFF)) { /** @phpstan-ignore-line */
                $this->markTestSkipped('Cannot set Mysqli error report mode to MYSQLI_REPORT_OFF');
            }
        }
    }

    protected function getFactoryNamespace(): string
    {
        return 'EngineWorks\DBAL\Mysqli';
    }

    protected function getSettingsArray(): array
    {
        return [
            'host' => $this->getenv('testMysqli_server'),
            'port' => $this->getenv('testMysqli_port'),
            'database' => '',
            'user' => $this->getenv('testMysqli_username'),
            'password' => $this->getenv('testMysqli_password'),
        ];
    }

    protected function createDatabaseStructure(): void
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
