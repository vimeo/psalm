<?php

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;

require_once __DIR__ . '/extending_plugin.php';

class ExtendingPluginRegistration implements PluginEntryPointInterface
{
    /** @return void */
    public function __invoke(RegistrationInterface $r, SimpleXMLElement $config = null)
    {
        $r->registerHooksFromClass(ExtendingPlugin::class);
    }
}
