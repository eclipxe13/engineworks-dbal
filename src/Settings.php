<?php

declare(strict_types=1);

namespace EngineWorks\DBAL;

/**
 * Interface Settings, this must be implemented by drivers
 */
interface Settings
{
    /**
     * Settings constructor.
     *
     * @param array<scalar|null> $settings Subset of settings to initialize in the object
     */
    public function __construct(array $settings = []);

    /**
     * Get a setting
     *
     * @param string $name
     * @param scalar|null $default
     * @return scalar|null
     */
    public function get(string $name, $default = null);

    /**
     * Check if a setting string exists
     *
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool;
}
