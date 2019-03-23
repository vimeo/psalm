<?php
namespace Psalm\Test\Plugin;

use Psalm\Plugin;
use SimpleXMLElement;

/** @psalm-suppress UnusedClass */
class AfterAnalysisPlugin implements \Psalm\Plugin\PluginEntryPointInterface
{
    /** @return void */
    public function __invoke(Plugin\RegistrationInterface $registration, SimpleXMLElement $config = null)
    {
        require_once __DIR__ . '/Hook/AfterAnalysis.php';

        $registration->registerHooksFromClass(Hook\AfterAnalysis::class);
    }
}
