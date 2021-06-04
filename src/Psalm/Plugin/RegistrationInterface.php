<?php
namespace Psalm\Plugin;

use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Scanner\FileScanner;

interface RegistrationInterface
{
    public function addStubFile(string $file_name): void;

    /**
     * @param string class-string $handler
     */
    public function registerHooksFromClass(string $handler): void;

    /**
     * @param string $fileExtension e.g. `'html'`
     * @param class-string<FileScanner> $className
     */
    public function addFileTypeScanner(string $fileExtension, string $className): void;

    /**
     * @param string $fileExtension e.g. `'html'`
     * @param class-string<FileAnalyzer> $className
     */
    public function addFileTypeAnalyzer(string $fileExtension, string $className): void;
}
