<?php
namespace Psalm\Test\Config\Plugin;

use Psalm\Plugin;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
class PropertyPlugin implements \Psalm\Plugin\PluginEntryPointInterface
{
    /** @return void */
    public function __invoke(Plugin\RegistrationInterface $registration, ?SimpleXMLElement $config = null)
    {
        require_once __DIR__ . '/Hook/FooPropertyProvider.php';

        $registration->registerHooksFromClass(Hook\FooPropertyProvider::class);
    }
}
