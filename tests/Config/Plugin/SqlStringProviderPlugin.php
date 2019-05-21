<?php
namespace Psalm\Test\Config\Plugin;

use Psalm\Plugin;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
class SqlStringProviderPlugin implements \Psalm\Plugin\PluginEntryPointInterface
{
    /** @return void */
    public function __invoke(Plugin\RegistrationInterface $registration, SimpleXMLElement $config = null)
    {
        require_once __DIR__ . '/Hook/SqlStringProvider.php';
        require_once __DIR__ . '/Hook/StringProvider/TSqlSelectString.php';

        $registration->registerHooksFromClass(Hook\SqlStringProvider::class);
    }
}
