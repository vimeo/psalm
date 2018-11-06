<?php
namespace Psalm;

use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\PluginApi;
use SimpleXMLElement;

class FileBasedPluginAdapter implements PluginApi\PluginEntryPointInterface
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
    public function __invoke(PluginApi\RegistrationInterface $registration, SimpleXMLElement $config = null)
    {
        $fq_class_name = $this->getPluginClassForPath($this->path, Plugin::class);

        /** @psalm-suppress UnresolvableInclude */
        require_once($this->path);

        $registration->registerHooksFromClass($fq_class_name);
    }

    private function getPluginClassForPath(string $path, string $must_extend): string
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

        if (!$codebase->classExtends(
            $fq_class_name,
            $must_extend
        )
        ) {
            throw new \InvalidArgumentException(
                'This plugin must extend ' . $must_extend . ' - ' . $path . ' does not'
            );
        }

        return $fq_class_name;
    }
}
