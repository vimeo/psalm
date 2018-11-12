<?php
namespace Psalm\Example\Plugin\ComposerBased;

use Psalm\Plugin;
use SimpleXMLElement;

class PluginEntryPoint implements Plugin\PluginEntryPointInterface
{
    /** @return void */
    public function __invoke(Plugin\RegistrationInterface $registration, ?SimpleXMLElement $config = null)
    {
        require_once __DIR__ . '/EchoChecker.php';
        $registration->registerHooksFromClass(EchoChecker::class);
    }
}
