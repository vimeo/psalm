<?php
namespace Psalm\PluginManager;

use RuntimeException;

class ComposerLock
{
    /** @var string */
    private $file_name;

    public function __construct(string $file_name)
    {
        $this->file_name = $file_name;
    }


    /**
     * @param mixed $package
     * @psalm-assert-if-true array{type:'psalm-plugin',name:string,extra:array{psalm:array{pluginClass:string}}}
     *                        $package
     */
    public function isPlugin($package): bool
    {
        return is_array($package)
            && isset($package['name'])
            && is_string($package['name'])
            && isset($package['type'])
            && $package['type'] === 'psalm-plugin'
            && isset($package['extra']['psalm']['pluginClass'])
            && is_string($package['extra']['psalm']['pluginClass']);
    }

    /**
     * @return array<string,string> [packageName => pluginClass, ...]
     */
    public function getPlugins(): array
    {
        $pluginPackages = $this->getAllPluginPackages();
        $ret = [];
        foreach ($pluginPackages as $package) {
            $ret[$package['name']] = $package['extra']['psalm']['pluginClass'];
        }
        return $ret;
    }

    private function read(): array
    {
        /** @psalm-suppress MixedAssignment */
        $contents = json_decode(file_get_contents($this->file_name), true);

        if ($error = json_last_error()) {
            throw new RuntimeException(json_last_error_msg(), $error);
        }

        if (!is_array($contents)) {
            throw new RuntimeException('Malformed ' . $this->file_name . ', expecting JSON-encoded object');
        }

        return $contents;
    }

    /**
     * @return array<mixed,array{name:string,type:string,extra:array{psalm:array{pluginClass:string}}}>
     */
    private function getAllPluginPackages(): array
    {
        $packages = $this->getAllPackages();
        $ret = [];
        /** @psalm-suppress MixedAssignment */
        foreach ($packages as $package) {
            if ($this->isPlugin($package)) {
                $ret[] = $package;
            }
        }
        return $ret;
    }

    private function getAllPackages(): array
    {
        $composer_lock_contents = $this->read();
        if (!isset($composer_lock_contents["packages"]) || !is_array($composer_lock_contents["packages"])) {
            throw new RuntimeException('packages section is missing or not an array');
        }
        if (!isset($composer_lock_contents["packages-dev"]) || !is_array($composer_lock_contents["packages-dev"])) {
            throw new RuntimeException('packages-dev section is missing or not an array');
        }
        return array_merge($composer_lock_contents["packages"], $composer_lock_contents["packages-dev"]);
    }
}
