<?php
namespace Psalm\Plugin\Hook;

use Psalm\Internal\Analyzer\FileAnalyzer;

interface BeforeAnalyzeFileInterface
{
    /**
     * @return void
     */
    public static function beforeAnalyzeFile(
        FileAnalyzer $file_analyzer
    );
}
