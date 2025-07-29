<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin;

use Override;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use Psalm\Test\Config\Plugin\Hook\FooMethodProvider;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
final class MethodPlugin implements PluginEntryPointInterface
{
    #[Override]
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/Hook/FooMethodProvider.php';

        $registration->registerHooksFromClass(FooMethodProvider::class);
    }
}
