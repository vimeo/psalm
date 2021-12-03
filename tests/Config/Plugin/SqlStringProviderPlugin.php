<?php
namespace Psalm\Test\Config\Plugin;

use Psalm\Plugin;
use Psalm\Plugin\PluginEntryPointInterface;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
class SqlStringProviderPlugin implements PluginEntryPointInterface
{
    public function __invoke(Plugin\RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/Hook/SqlStringProvider.php';
        require_once __DIR__ . '/Hook/StringProvider/TSqlSelectString.php';

        $registration->registerHooksFromClass(Hook\SqlStringProvider::class);
    }
}
