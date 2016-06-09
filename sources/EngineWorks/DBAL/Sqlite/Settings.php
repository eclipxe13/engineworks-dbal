<?php namespace EngineWorks\DBAL\Sqlite;

use EngineWorks\DBAL\Abstracts\SettingsMap;

class Settings extends SettingsMap
{
    /** @var array */
    protected $map = [
        'filename' => '',
        'prefix' => '',             // this is a interface setting
        'flags' => null,
        'dump' => '',            // '' => nothing, 'info' => '-- info', 'debug' => SELECT... + info
    ];
}
