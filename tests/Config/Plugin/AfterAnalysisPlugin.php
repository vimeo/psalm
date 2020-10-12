<?php
namespace Psalm\Test\Config\Plugin;

use Psalm\Plugin;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
class AfterAnalysisPlugin implements \Psalm\Plugin\PluginEntryPointInterface
{
    public function __invoke(Plugin\RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/Hook/AfterAnalysis.php';

        $registration->registerHooksFromClass(Hook\AfterAnalysis::class);
    }
}
