<?php

namespace EngineWorks\DBAL;

use Psr\Log\LoggerInterface;

class Factory
{
    /** @var string */
    private $namespace;

    /** @var string */
    private $dbalname;

    /** @var string */
    private $settingsname;

    /**
     * @param string $namespace by example EngineWorks\DBAL\Mysqli
     * @param string $dbalname
     * @param string $settingsname
     */
    public function __construct($namespace, $dbalname = 'DBAL', $settingsname = 'Settings')
    {
        $this->namespace = $namespace;
        $this->dbalname = $dbalname;
        $this->settingsname = $settingsname;
    }

    /**
     * Return a valid class name (namespace + class),
     * optionally checks if the class extends or implements other classes
     * @param string $class
     * @param string $extends
     * @param string $implements
     * @return string
     */
    protected function buildClassName($class, $extends = "", $implements = "")
    {
        $classname = $this->namespace . '\\' . $class;
        if (! class_exists($classname)) {
            throw new \LogicException("Class $classname does not exists");
        }
        if ("" !== $extends) {
            if (! in_array($extends, class_parents($classname))) {
                throw new \LogicException("Class $classname does not extends $extends");
            }
        }
        if ("" !== $implements) {
            if (! in_array($implements, class_implements($classname))) {
                throw new \LogicException("Class $classname does not implements $implements");
            }
        }
        return $classname;
    }

    /**
     * @param Settings $settings
     * @param LoggerInterface|null $logger
     * @return DBAL
     */
    public function dbal(Settings $settings, LoggerInterface $logger = null)
    {
        $classname = $this->buildClassName($this->dbalname, DBAL::class, "");
        return new $classname($settings, $logger);
    }

    /**
     * @param array $settings
     * @return Settings
     */
    public function settings(array $settings = null)
    {
        $classname = $this->buildClassName($this->settingsname, "", Settings::class);
        return new $classname($settings);
    }
}
