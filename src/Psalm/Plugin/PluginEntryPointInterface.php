<?php
namespace Psalm\Plugin;

use SimpleXMLElement;
use Psalm\Plugin\RegistrationInterface;

interface PluginEntryPointInterface
{
    /** @return void */
    public function __invoke(RegistrationInterface $api, SimpleXMLElement $config = null);
}
