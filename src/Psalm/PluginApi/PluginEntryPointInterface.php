<?php
namespace Psalm\PluginApi;

use SimpleXMLElement;

interface PluginEntryPointInterface
{
    /** @return void */
    public function __invoke(RegistrationInterface $api, SimpleXMLElement $config = null);
}
