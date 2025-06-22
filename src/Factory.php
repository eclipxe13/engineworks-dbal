<?php

declare(strict_types=1);

namespace EngineWorks\DBAL;

use LogicException;
use Psr\Log\LoggerInterface;

class Factory
{
    /** @var string */
    private $namespace;

    /** @var string */
    private $dbalName;

    /** @var string */
    private $settingsName;

    /**
     * @param string $namespace in example EngineWorks\DBAL\Mysqli
     * @param string $dbalName
     * @param string $settingsName
     */
    public function __construct(string $namespace, string $dbalName = 'DBAL', string $settingsName = 'Settings')
    {
        $this->namespace = $namespace;
        $this->dbalName = $dbalName;
        $this->settingsName = $settingsName;
    }

    /**
     * Return a valid class name (namespace + class),
     * optionally checks if the class extends or implements other classes
     *
     * @param string $class
     * @param string $extends
     * @param string $implements
     * @return class-string
     */
    protected function buildClassName(string $class, string $extends = '', string $implements = ''): string
    {
        $classname = $this->namespace . '\\' . $class;
        if (! class_exists($classname)) {
            throw new LogicException("Class $classname does not exists");
        }
        if ('' !== $extends) {
            if (! in_array($extends, class_parents($classname) ?: [])) {
                throw new LogicException("Class $classname does not extends $extends");
            }
        }
        if ('' !== $implements) {
            if (! in_array($implements, class_implements($classname) ?: [])) {
                throw new LogicException("Class $classname does not implements $implements");
            }
        }
        return $classname;
    }

    /**
     * @param Settings $settings
     * @param LoggerInterface|null $logger
     * @return DBAL
     */
    public function dbal(Settings $settings, ?LoggerInterface $logger = null): DBAL
    {
        $classname = $this->buildClassName($this->dbalName, '', DBAL::class);
        $dbal = new $classname($settings, $logger);
        if (! $dbal instanceof DBAL) {
            throw new LogicException(sprintf('The object with class %s was created but is not a DBAL', $classname));
        }
        return $dbal;
    }

    /**
     * @param mixed[] $settings
     * @return Settings
     */
    public function settings(array $settings = []): Settings
    {
        $classname = $this->buildClassName($this->settingsName, '', Settings::class);
        $settings = new $classname($settings);
        if (! $settings instanceof Settings) {
            throw new LogicException(sprintf('The object with class %s was created but is not a Settings', $classname));
        }
        return $settings;
    }
}
