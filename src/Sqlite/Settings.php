<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace EngineWorks\DBAL\Sqlite;

use EngineWorks\DBAL\Abstracts\SettingsMap;

/**
 * Settings for a sqlite connection
 *
 * - filename: database resource
 * - enable-exceptions: sqlite will throw \Exceptions instead of errors
 * - prefix: tables prefix
 * - flags: 6 => SQLITE3_OPEN_CREATE + SQLITE3_OPEN_READWRITE
 * - dump: '' => nothing, 'info' => '-- info messages',  'debug' => SELECT... + info
 *
 * @package EngineWorks\DBAL\Sqlite
 */
class Settings extends SettingsMap
{
    protected $map = [
        'filename' => ':memory:',
        'enable-exceptions' => false,
        'prefix' => '',
        'flags' => SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,
        'dump' => '',
    ];
}
