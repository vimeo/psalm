<?php
namespace Psalm;

use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Scanner\FileScanner;
use Psalm\PluginApi;
use SimpleXMLElement;

class FileBasedPluginAdapter implements PluginApi\PluginEntryPointInterface
{
    /** @var string */
    private $path;

    /** @var ProjectChecker */
    private $project_checker;

    /** @var Config */
    private $config;

    public function __construct(string $path, Config $config, ProjectChecker $project_checker)
    {
        $this->path = $path;
        $this->config = $config;
        $this->project_checker = $project_checker;
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
        $codebase = $this->project_checker->codebase;

        $file_storage = $codebase->createFileStorageForPath($path);
        $file_to_scan = new FileScanner($path, $this->config->shortenFileName($path), true);
        $file_to_scan->scan(
            $codebase,
            $file_storage
        );

        $declared_classes = ClassLikeChecker::getClassesForFile($codebase, $path);

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
