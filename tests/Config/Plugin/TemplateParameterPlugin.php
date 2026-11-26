<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin;

use Override;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use Psalm\Tests\Config\Plugin\Hook\TemplateParameterProvider;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
final class TemplateParameterPlugin implements PluginEntryPointInterface
{
    #[Override]
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/Hook/TemplateParameterProvider.php';

        $registration->registerHooksFromClass(TemplateParameterProvider::class);
    }
}
