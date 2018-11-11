<?php
namespace Psalm\PluginApi\Hook;

use PhpParser\Node\Stmt\ClassLike;
use Psalm\Codebase;
use Psalm\FileManipulation\FileManipulation;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;

interface AfterClassLikeVisitInterface
{
    /**
     * @param  FileManipulation[] $file_replacements
     *
     * @return void
     */
    public static function afterClassLikeVisit(
        ClassLike $stmt,
        ClassLikeStorage $storage,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    );
}
