<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\Sample;

use EngineWorks\DBAL\Abstracts\SettingsMap;

class SettingsMapExtension extends SettingsMap
{
    protected $map = [
        'foo' => null,
        'bar' => true,
    ];
}
