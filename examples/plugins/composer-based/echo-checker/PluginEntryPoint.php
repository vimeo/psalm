<?php

namespace Psalm\Example\Plugin\ComposerBased;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

final class PluginEntryPoint implements PluginEntryPointInterface
{
    #[\Override]
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/EchoChecker.php';
        $registration->registerHooksFromClass(EchoChecker::class);
    }
}
