<?php
namespace Psalm\Test\Config\Plugin;

use Psalm\Plugin;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
class PropertyPlugin implements \Psalm\Plugin\PluginEntryPointInterface
{
    public function __invoke(Plugin\RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/Hook/FooPropertyProvider.php';

        $registration->registerHooksFromClass(Hook\FooPropertyProvider::class);
    }
}
