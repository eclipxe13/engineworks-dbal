<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Mssql;

use EngineWorks\DBAL\Abstracts\SettingsMap;

/**
 * Settings for a mysql connection
 *
 * - host: server host name or ip (localhost)
 * - port: server port number (3306)
 * - user: server username
 * - password: server password
 * - database: server catalog
 * - encoding: server encoding (UTF8)
 * - connect-timeout: timeout for server connection (5)
 * - socket: socket connection (null)
 * - prefix: tables prefix
 * - flags: null
 * - dump: '' => nothing, 'info' => '-- info messages',  'debug' => SELECT... + info
 *
 * @package EngineWorks\DBAL\Mysqli
 */
class Settings extends SettingsMap
{
    protected $map = [
        'host' => 'localhost',
        'port' => 1433,
        'user' => '',
        'password' => '',
        'database' => '',
        'encoding' => 'UTF-8',
        'prefix' => '',
        'connect-timeout' => 5,
        'freetds-version' => '7.0',
        'socket' => null,
        'flags' => null,
        'dump' => '',
    ];
}
