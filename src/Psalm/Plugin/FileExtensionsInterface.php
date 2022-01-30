<?php

namespace Psalm\Plugin;

use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Scanner\FileScanner;

interface FileExtensionsInterface
{
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
