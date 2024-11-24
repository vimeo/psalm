<?php

declare(strict_types=1);

namespace Psalm;

use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;
use UnexpectedValueException;

use function assert;
use function class_exists;
use function count;
use function reset;
use function str_replace;

use const DIRECTORY_SEPARATOR;

/** @internal */
final class FileBasedPluginAdapter implements PluginEntryPointInterface
{
    private readonly string $path;

    public function __construct(
        string $path,
        private readonly Config $config,
        private readonly Codebase $codebase,
    ) {
        if (!$path) {
            throw new UnexpectedValueException('$path cannot be empty');
        }

        $this->path = $path;
    }

    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        $fq_class_name = $this->getPluginClassForPath($this->path);

        /** @psalm-suppress UnresolvableInclude */
        require_once($this->path);

        assert(class_exists($fq_class_name));

        $registration->registerHooksFromClass($fq_class_name);
    }

    private function getPluginClassForPath(string $path): string
    {
        $codebase = $this->codebase;

        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        $file_storage = $codebase->createFileStorageForPath($path);
        $file_to_scan = new FileScanner($path, $this->config->shortenFileName($path), true);
        $file_to_scan->scan(
            $codebase,
            $file_storage,
        );

        $declared_classes = ClassLikeAnalyzer::getClassesForFile($codebase, $path);

        assert(count($declared_classes) > 0, 'FileBasedPlugin contains a class');

        return reset($declared_classes);
    }
}
