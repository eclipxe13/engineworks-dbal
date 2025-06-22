<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Sqlsrv;

use EngineWorks\DBAL\Abstracts\SettingsMap;

/**
 * Settings for a mysql connection
 *
 * - host: server host name or ip (localhost)
 * - port: server port number (1433)
 * - user: server username
 * - password: server password
 * - database: server catalog
 * - encoding: connection encoding (PDO::SQLSRV_ENCODING_DEFAULT)
 *   @see https://learn.microsoft.com/en-us/sql/connect/php/constants-microsoft-drivers-for-php-for-sql-server\
 *        ?view=sql-server-ver17#encoding-constants
 * - prefix: tables prefix
 * - connect-timeout: timeout for server connection (5)
 * - timeout: timeout for running queries (0)
 * - encrypt: whether the communication with SQL Server is encrypted (false)
 * - trust-server-certificate: whether the client should trust or reject a self-signed server certificate (true)
 * - dump: '' => nothing, 'info' => '-- info messages',  'debug' => SELECT... + info
 */
class Settings extends SettingsMap
{
    protected $map = [
        'host' => 'localhost',
        'port' => 1433,
        'user' => '',
        'password' => '',
        'database' => '',
        'encoding' => null,
        'prefix' => '',
        'connect-timeout' => 5, // the default timeout can be more than 15 seconds
        'timeout' => 0,
        'encrypt' => false,
        'trust-server-certificate' => true,
        'dump' => '',
    ];
}
