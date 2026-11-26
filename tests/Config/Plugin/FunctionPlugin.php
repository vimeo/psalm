<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin;

use Override;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use Psalm\Test\Config\Plugin\Hook\CallableArgParamsProvider;
use Psalm\Test\Config\Plugin\Hook\MagicFunctionProvider;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
final class FunctionPlugin implements PluginEntryPointInterface
{
    #[Override]
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/Hook/CallableArgParamsProvider.php';
        require_once __DIR__ . '/Hook/MagicFunctionProvider.php';

        $registration->registerHooksFromClass(CallableArgParamsProvider::class);
        $registration->registerHooksFromClass(MagicFunctionProvider::class);
    }
}
