<?php
namespace Psalm\PluginApi;
use SimpleXmlElement;

interface PluginEntryPointInterface
{
    /** @return void */
    public function __invoke(RegistrationInterface $api, ?SimpleXmlElement $config = null);
}
