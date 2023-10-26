<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use Psalm\Tests\Config\Plugin\Hook\CustomArrayMapFunctionStorageProvider;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
class StoragePlugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/Hook/CustomArrayMapFunctionStorageProvider.php';

        $registration->registerHooksFromClass(CustomArrayMapFunctionStorageProvider::class);
    }
}
