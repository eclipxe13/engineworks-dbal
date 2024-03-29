<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Sqlsrv;

use EngineWorks\DBAL\Abstracts\SettingsMap;

/**
 * Settings for a mysql connection
 *
 * - host: server host name or ip (localhost)
 * - port: server port number (3306)
 * - user: server username
 * - password: server password
 * - database: server catalog
 * - encoding: server encoding (UTF8) (NOT IMPLEMENTED YET)
 * - connect-timeout: timeout for server connection (5)
 * - timeout: timeout for running queries (5)
 * - prefix: tables prefix
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
        // 'encoding' => 'UTF-8', // todo: enable encoding
        'prefix' => '',
        'connect-timeout' => 5, // the default timeout can be more than 15 seconds
        'timeout' => 0,
        'dump' => '',
    ];
}
