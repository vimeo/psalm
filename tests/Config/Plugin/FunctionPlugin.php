<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin;

use Override;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use Psalm\Tests\Config\Plugin\Hook\CallableArgParamsProvider;
use Psalm\Tests\Config\Plugin\Hook\MagicFunctionProvider;
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
