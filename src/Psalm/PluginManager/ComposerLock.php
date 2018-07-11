<?php
namespace Psalm\PluginManager;

class ComposerLock
{
    /** @var string */
    private $file_name;

    public function __construct(string $file_name)
    {
        $this->file_name = $file_name;
    }

    public function isPlugin(object $package): bool
    {
        return isset($package->type)
            && $package->type === 'psalm-plugin'
            && isset($package->extra->pluginClass);
    }

    public function getPlugins(): array
    {
        $pluginPackages = $this->getAllPluginPackages();
        $ret = [];
        foreach ($pluginPackages as $package) {
            $ret[$package->name] = $package->extra->pluginClass;
        }
        return $ret;
    }

    private function read(): object
    {
        return json_decode(file_get_contents($this->file_name));
    }

    private function getAllPluginPackages(): array
    {
        return array_filter(
            $this->getAllPackages(),
            [$this, 'isPlugin']
        );
    }

    private function getAllPackages(): array
    {
        $composer_lock_contents = $this->read();
        return array_merge($composer_lock_contents->packages, $composer_lock_contents->{"packages-dev"});
    }

}
