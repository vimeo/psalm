<?php
namespace Psalm\PluginManager;

class PluginList
{
    /** @var ConfigFile */
    private $config_file;

    /** @var ComposerLock */
    private $composer_lock;

    /** @var ?array<string,string> */
    private $all_plugins = null;

    /** @var ?array<string,string> */
    private $enabled_plugins = null;

    public function __construct(ConfigFile $config_file, ComposerLock $composer_lock)
    {
        $this->config_file = $config_file;
        $this->composer_lock = $composer_lock;
    }

    /** @return array<string,string> */
    public function getEnabled(): array
    {
        if (!$this->enabled_plugins) {
            $this->enabled_plugins = $this->config_file->getConfig()->getPluginClasses();
        }
        return $this->enabled_plugins;
    }

    /** @return array<string,string> */
    public function getAvailable(): array
    {
        return array_diff($this->getAll(), $this->getEnabled());
    }

    /** @return array<string,string> */
    public function getAll(): array
    {
        if (null == $this->all_plugins) {
            $this->all_plugins = $this->composer_lock->getPlugins();
        }
        return $this->all_plugins;
    }

    public function resolvePluginClass(string $class_or_package): string
    {
        $plugin_classes = $this->getAll();

        if (false !== strpos($class_or_package, '/') && isset($plugin_classes[$class_or_package])) {
            return $plugin_classes[$class_or_package];
        } else if (in_array($class_or_package, $plugin_classes, true)){
            return $class_or_package;
        }

        throw new \InvalidArgumentException('Unknown plugin: ' . $class_or_package);
    }

    public function isEnabled(string $class): bool
    {
        return in_array($class, $this->getEnabled(), true);
    }
}
