<?php

namespace Psalm\Test\Config\Plugin;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use Psalm\Test\Config\Plugin\Hook\SqlStringProvider;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
class SqlStringProviderPlugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/Hook/SqlStringProvider.php';
        require_once __DIR__ . '/Hook/StringProvider/TSqlSelectString.php';

        $registration->registerHooksFromClass(SqlStringProvider::class);
    }
}
