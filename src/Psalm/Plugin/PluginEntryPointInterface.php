<?php

namespace Psalm\Plugin;

use SimpleXMLElement;

interface PluginEntryPointInterface extends PluginInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void;
}
