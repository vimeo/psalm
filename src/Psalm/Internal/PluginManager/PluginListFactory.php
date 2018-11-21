<?php
namespace Psalm\Internal\PluginManager;

class PluginListFactory
{
    public function __invoke(string $current_dir, string $config_file_path = null): PluginList
    {
        $stub_composer_lock = (object)[
            "packages" => [],
            "packages-dev" => [],
        ];

        $config_file = new ConfigFile($current_dir, $config_file_path);
        $lock_file = is_readable('composer.lock') ?
            'composer.lock' :
            'data:application/json,' . urlencode(json_encode($stub_composer_lock));

        $composer_lock = new ComposerLock($lock_file);
        return new PluginList($config_file, $composer_lock);
    }
}
