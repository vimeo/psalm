<?php
namespace Vimeo\CodeAnalysis\EchoChecker;

use Psalm\PluginApi;
use SimpleXMLElement;

class PluginEntryPoint implements PluginApi\PluginEntryPointInterface
{
    /** @return void */
    public function __invoke(PluginApi\RegistrationInterface $registration, ?SimpleXMLElement $config = null)
    {
        require_once __DIR__ . '/EchoChecker.php';
        $registration->registerHooksFromClass(EchoChecker::class);
    }
}
