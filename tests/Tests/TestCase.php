<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function getenv(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        if (is_string($value)) {
            return $value;
        }
        if (is_scalar($value)) {
            return (string) $value;
        }
        return $default;
    }
}
