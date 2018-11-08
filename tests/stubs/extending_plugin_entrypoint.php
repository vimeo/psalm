<?php

use Psalm\PluginApi\PluginEntryPointInterface;
use Psalm\PluginApi\RegistrationInterface;

require_once __DIR__ . '/extending_plugin.php';

class ExtendingPluginRegistration implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $r, SimpleXMLElement $config = null): void
    {
        $r->registerHooksFromClass(ExtendingPlugin::class);
    }
}
