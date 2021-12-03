<?php
namespace Psalm\Test\Config\Plugin;

use Psalm\Plugin;
use Psalm\Plugin\PluginEntryPointInterface;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
class FunctionPlugin implements PluginEntryPointInterface
{
    public function __invoke(Plugin\RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/Hook/MagicFunctionProvider.php';

        $registration->registerHooksFromClass(Hook\MagicFunctionProvider::class);
    }
}
