<?php namespace EngineWorks\DBAL\Sqlite;

use EngineWorks\DBAL\Abstracts\SettingsMap;

/**
 * Settings for a sqlite connection
 *
 * - filename: database resource
 * - prefix: tables prefix
 * - flags: null
 * - dump: '' => nothing, 'info' => '-- info messages',  'debug' => SELECT... + info
 *
 * @package EngineWorks\DBAL\Sqlite
 */
class Settings extends SettingsMap
{
    protected $map = [
        'filename' => '',
        'prefix' => '',
        'flags' => null,
        'dump' => '',
    ];
}
