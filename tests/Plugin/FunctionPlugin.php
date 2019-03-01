<?php
namespace Psalm\Test\Plugin;

use Psalm\Plugin;
use SimpleXMLElement;

class FunctionPlugin implements \Psalm\Plugin\PluginEntryPointInterface
{
    /** @return void */
    public function __invoke(Plugin\RegistrationInterface $registration, SimpleXMLElement $config = null)
    {
        require_once __DIR__ . '/Hook/MagicFunctionProvider.php';

        $registration->registerHooksFromClass(Hook\MagicFunctionProvider::class);
    }
}
