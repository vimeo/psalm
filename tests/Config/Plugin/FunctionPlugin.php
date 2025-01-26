<?php

namespace Psalm\Test\Config\Plugin;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use Psalm\Test\Config\Plugin\Hook\CallUserFuncLikeParamsProvider;
use Psalm\Test\Config\Plugin\Hook\MagicFunctionProvider;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
class FunctionPlugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/Hook/CallUserFuncLikeParamsProvider.php';
        require_once __DIR__ . '/Hook/MagicFunctionProvider.php';

        $registration->registerHooksFromClass(CallUserFuncLikeParamsProvider::class);
        $registration->registerHooksFromClass(MagicFunctionProvider::class);
    }
}
