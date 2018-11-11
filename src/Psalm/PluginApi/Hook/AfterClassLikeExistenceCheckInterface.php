<?php
namespace Psalm\PluginApi\Hook;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\FileManipulation\FileManipulation;
use Psalm\StatementsSource;

interface AfterClassLikeExistenceCheckInterface
{
    /**
     * @param  FileManipulation[] $file_replacements
     *
     * @return void
     */
    public static function afterClassLikeExistenceCheck(
        string $fq_class_name,
        CodeLocation $code_location,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    );
}
