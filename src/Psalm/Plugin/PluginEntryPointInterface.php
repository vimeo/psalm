<?php

declare(strict_types=1);

namespace Psalm\Plugin;

use SimpleXMLElement;

interface PluginEntryPointInterface extends PluginInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void;
}
