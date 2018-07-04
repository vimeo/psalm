<?php
namespace Psalm;
use Psalm\PluginApi\RegistrationInterface;

class PluginFacade implements RegistrationInterface
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
