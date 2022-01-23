<?php

namespace Psalm\Tests\Config\Plugin;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use Psalm\Tests\Config\Plugin\Hook\ArrayMapStorageProvider;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
class StoragePlugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/Hook/ArrayMapStorageProvider.php';

        $registration->registerHooksFromClass(ArrayMapStorageProvider::class);
    }
}
