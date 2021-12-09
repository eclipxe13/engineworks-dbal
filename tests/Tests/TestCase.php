<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function getenv(string $key, string $default = ''): string
    {
        return strval($_ENV[$key] ?? $_SERVER[$key] ?? $default);
    }
}
