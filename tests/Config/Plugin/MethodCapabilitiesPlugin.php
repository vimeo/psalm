<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin;

use Override;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use Psalm\Test\Config\Plugin\Hook\KeyedGetterCapabilitiesProvider;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
final class MethodCapabilitiesPlugin implements PluginEntryPointInterface
{
    #[Override]
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/Hook/KeyedGetterCapabilitiesProvider.php';

        $registration->registerHooksFromClass(KeyedGetterCapabilitiesProvider::class);
    }
}
