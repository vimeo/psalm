<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use Psalm\Test\Config\Plugin\Hook\AfterAnalysis;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
class AfterAnalysisPlugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/Hook/AfterAnalysis.php';

        $registration->registerHooksFromClass(AfterAnalysis::class);
    }
}
