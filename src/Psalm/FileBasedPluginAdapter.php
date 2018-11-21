<?php
namespace Psalm;

use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Scanner\FileScanner;
use SimpleXMLElement;

class FileBasedPluginAdapter implements Plugin\PluginEntryPointInterface
{
    /** @var string */
    private $path;

    /** @var Codebase */
    private $codebase;

    /** @var Config */
    private $config;

    public function __construct(string $path, Config $config, Codebase $codebase)
    {
        $this->path = $path;
        $this->config = $config;
        $this->codebase = $codebase;
    }

    /** @return void */
    public function __invoke(Plugin\RegistrationInterface $registration, SimpleXMLElement $config = null)
    {
        $fq_class_name = $this->getPluginClassForPath($this->path);

        /** @psalm-suppress UnresolvableInclude */
        require_once($this->path);

        $registration->registerHooksFromClass($fq_class_name);
    }

    private function getPluginClassForPath(string $path): string
    {
        $codebase = $this->codebase;

        $file_storage = $codebase->createFileStorageForPath($path);
        $file_to_scan = new FileScanner($path, $this->config->shortenFileName($path), true);
        $file_to_scan->scan(
            $codebase,
            $file_storage
        );

        $declared_classes = ClassLikeAnalyzer::getClassesForFile($codebase, $path);

        if (count($declared_classes) !== 1) {
            throw new \InvalidArgumentException(
                'Plugins must have exactly one class in the file - ' . $path . ' has ' .
                    count($declared_classes)
            );
        }

        $fq_class_name = reset($declared_classes);

        return $fq_class_name;
    }
}
