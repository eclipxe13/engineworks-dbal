<?php namespace EngineWorks\DBAL\Mysqli;

use EngineWorks\DBAL\Abstracts\SettingsMap;
use \EngineWorks\DBAL\Settings as SettingsInterface;

/**
 * Class Settings
 * @package EngineWorks\DBAL\Mysqli
 */
class Settings extends SettingsMap implements SettingsInterface
{
    /** @var array */
    protected $map = [
        'host' => 'localhost',
        'port' => 3306,
        'user' => '',
        'password' => '',
        'database' => '',
        'encoding' => 'UTF8',
        'prefix' => '',             // this is a interface setting
        'connect-timeout' => 5,     // connection timeout in seconds
        'socket' => null,
        'flags' => null,
        'dump' => '',            // '' => nothing, 'info' => '-- info', 'debug' => SELECT... + info
    ];
}
