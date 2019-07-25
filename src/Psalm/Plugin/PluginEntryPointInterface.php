<?php
namespace Psalm\Plugin;

use SimpleXMLElement;

interface PluginEntryPointInterface
{
    /** @return void */
    public function __invoke(RegistrationInterface $api, SimpleXMLElement $config = null);
}
