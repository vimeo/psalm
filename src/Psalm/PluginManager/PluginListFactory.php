<?php
namespace Psalm\PluginManager;

class PluginListFactory
{
    public function __invoke(string $current_dir, string $config_file_path = null): PluginList
    {
        $config_file = new ConfigFile($current_dir, $config_file_path);
        $composer_lock = new ComposerLock('composer.lock');
        return new PluginList($config_file, $composer_lock);
    }
}
