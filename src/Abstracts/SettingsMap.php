<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Abstracts;

use EngineWorks\DBAL\Settings as SettingsInterface;
use InvalidArgumentException;

/**
 * This is a utility class to implement the Settings interface, it is used
 * on Mysqli and Sqlite implementations.
 * On the extended class define the map of properties
 *
 * @package EngineWorks\DBAL\Abstracts
 */
class SettingsMap implements SettingsInterface
{
    /**
     * map of settings with default values
     * @var array<string, mixed>
     */
    protected $map = [];

    public function __construct(array $settings = [])
    {
        $this->setAll($settings);
    }

    /**
     * Get all the settings
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->map;
    }

    /**
     * Set a setting, if it does dot exists then throws an exception
     *
     * @param string $name
     * @param mixed $value
     * @throws InvalidArgumentException if setting does not exists
     */
    public function set(string $name, $value): void
    {
        if (! array_key_exists($name, $this->map)) {
            throw new InvalidArgumentException("Setting $name does not exists");
        }
        $this->map[$name] = $value;
    }

    /**
     * Set an array of settings, ignores non existent or non-string-key elements
     *
     * @param mixed[] $settings
     */
    public function setAll(array $settings): void
    {
        foreach ($settings as $name => $value) {
            if (is_string($name) && array_key_exists($name, $this->map)) {
                $this->map[$name] = $value;
            }
        }
    }

    /**
     * Obtain a setting
     *
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get(string $name, $default = null)
    {
        return ($this->exists($name)) ? $this->map[$name] : $default;
    }

    public function exists(string $name): bool
    {
        return array_key_exists($name, $this->map);
    }
}
