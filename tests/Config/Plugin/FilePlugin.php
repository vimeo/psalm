<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use Psalm\Test\Config\Plugin\Hook\FileProvider;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
class FilePlugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/Hook/FileProvider.php';

        $registration->registerHooksFromClass(FileProvider::class);
    }
}
