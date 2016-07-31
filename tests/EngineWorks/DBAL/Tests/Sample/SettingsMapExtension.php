<?php
namespace EngineWorks\DBAL\Tests\Sample;

use EngineWorks\DBAL\Abstracts\SettingsMap;

class SettingsMapExtension extends SettingsMap
{
    protected $map = [
        'foo' => null,
        'bar' => true,
    ];
}
