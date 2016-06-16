<?php namespace EngineWorks\DBAL\Abstracts;

use EngineWorks\DBAL\Settings as SettingsInterface;

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
     * @var array
     */
    protected $map = [];

    public function __construct(array $settings = null)
    {
        if (null !== $settings) {
            $this->setAll($settings);
        }
    }

    /**
     * Get all the settings
     * @return array
     */
    public function all()
    {
        return $this->map;
    }

    /**
     * Set a setting, if it does dot exists then throws an exception
     *
     * @param string $name
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function set($name, $value)
    {
        if (! $this->exists($name)) {
            throw new \InvalidArgumentException("Setting $name does not exists");
        }
        $this->map[$name] = $value;
    }

    /**
     * Set an array of settings
     * @param array $settings
     */
    public function setAll(array $settings)
    {
        foreach ($settings as $name => $value) {
            if ($this->exists($name)) { // avoid the logic exception
                $this->set($name, $value);
            }
        }
    }

    public function get($name, $default = null)
    {
        return ($this->exists($name)) ? $this->map[$name] : $default;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function exists($name)
    {
        return (array_key_exists($name, $this->map));
    }
}
