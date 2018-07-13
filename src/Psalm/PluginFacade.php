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

    /** @return void */
    public function addStubFile(string $file_name)
    {
        $this->config->addStubFile($file_name);
    }
}
