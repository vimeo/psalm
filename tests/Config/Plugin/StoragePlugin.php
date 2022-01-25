<?php

namespace Psalm\Tests\Config\Plugin;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use Psalm\Tests\Config\Plugin\Hook\CustomArrayMapStorageProvider;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
class StoragePlugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/Hook/CustomArrayMapStorageProvider.php';

        $registration->registerHooksFromClass(CustomArrayMapStorageProvider::class);
    }
}
