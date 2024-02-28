<?php

namespace Psalm;

use LogicException;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Plugin\FileExtensionsInterface;

use function class_exists;
use function in_array;
use function is_a;
use function sprintf;

final class PluginFileExtensionsSocket implements FileExtensionsInterface
{
    private Config $config;

    /**
     * @var array<string, class-string<FileScanner>>
     */
    private array $additionalFileTypeScanners = [];

    /**
     * @var array<string, class-string<FileAnalyzer>>
     */
    private array $additionalFileTypeAnalyzers = [];

    /**
     * @var list<string>
     */
    private array $additionalFileExtensions = [];

    /**
     * @internal
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $fileExtension e.g. `'html'`
     * @param class-string<FileScanner> $className
     */
    public function addFileTypeScanner(string $fileExtension, string $className): void
    {
        if (!class_exists($className) || !is_a($className, FileScanner::class, true)) {
            throw new LogicException(
                sprintf(
                    'Class %s must be of type %s',
                    $className,
                    FileScanner::class,
                ),
                1_622_727_271,
            );
        }
        if (isset($this->config->getFiletypeScanners()[$fileExtension])
            || isset($this->additionalFileTypeScanners[$fileExtension])
        ) {
            throw new LogicException(
                sprintf('Cannot redeclare scanner for file-type %s', $fileExtension),
                1_622_727_272,
            );
        }
        $this->additionalFileTypeScanners[$fileExtension] = $className;
        $this->addFileExtension($fileExtension);
    }

    /**
     * @return array<string, class-string<FileScanner>>
     */
    public function getAdditionalFileTypeScanners(): array
    {
        return $this->additionalFileTypeScanners;
    }

    /**
     * @param string $fileExtension e.g. `'html'`
     * @param class-string<FileAnalyzer> $className
     */
    public function addFileTypeAnalyzer(string $fileExtension, string $className): void
    {
        if (!class_exists($className) || !is_a($className, FileAnalyzer::class, true)) {
            throw new LogicException(
                sprintf(
                    'Class %s must be of type %s',
                    $className,
                    FileAnalyzer::class,
                ),
                1_622_727_281,
            );
        }
        if (isset($this->config->getFiletypeAnalyzers()[$fileExtension])
            || isset($this->additionalFileTypeAnalyzers[$fileExtension])
        ) {
            throw new LogicException(
                sprintf('Cannot redeclare analyzer for file-type %s', $fileExtension),
                1_622_727_282,
            );
        }
        $this->additionalFileTypeAnalyzers[$fileExtension] = $className;
        $this->addFileExtension($fileExtension);
    }

    /**
     * @return array<string, class-string<FileAnalyzer>>
     */
    public function getAdditionalFileTypeAnalyzers(): array
    {
        return $this->additionalFileTypeAnalyzers;
    }

    /**
     * @return list<string> e.g. `['html', 'perl']`
     */
    public function getAdditionalFileExtensions(): array
    {
        return $this->additionalFileExtensions;
    }

    /**
     * @param string $fileExtension e.g. `'html'`
     */
    private function addFileExtension(string $fileExtension): void
    {
        /** @psalm-suppress RedundantCondition */
        if (!in_array($fileExtension, $this->additionalFileExtensions, true)
            && !in_array($fileExtension, $this->config->getFileExtensions(), true)
        ) {
            $this->additionalFileExtensions[] = $fileExtension;
        }
    }
}
