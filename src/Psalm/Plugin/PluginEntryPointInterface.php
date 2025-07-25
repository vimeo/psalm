<?php

declare(strict_types=1);

namespace Psalm\Plugin;

use Psalm\PluginRegistrationSocket;
use SimpleXMLElement;

interface PluginEntryPointInterface extends PluginInterface
{
    public function __invoke(PluginRegistrationSocket $registration, ?SimpleXMLElement $config = null): void;
}
