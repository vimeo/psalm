<?php
namespace Psalm;

/**
 * Represents the API available to plugins
 */
class PluginFacade
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @internal
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function addStubFile(string $file_name): void
    {
        $this->config->addStubFile($file_name);
    }
}
