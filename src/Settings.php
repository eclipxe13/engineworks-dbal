<?php
namespace EngineWorks\DBAL;

/**
 * Interface Settings, this must be implemented by drivers
 * @package EngineWorks\DBAL
 */
interface Settings
{
    /**
     * Settings constructor.
     *
     * @param array $settings Subset of settings to initialize in the object
     */
    public function __construct(array $settings = []);

    /**
     * Get a setting
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
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
