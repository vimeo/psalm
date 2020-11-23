<?php
namespace EventSourcing\Psalm;

use PhpParser\Node;
use Psalm\Codebase;
use Psalm\FileSource;
use Psalm\Plugin\Hook\AfterClassLikeVisitInterface;
use Psalm\Storage\ClassLikeStorage;
use ReflectionClass;

/**
 * Suppress issues dynamically based on interface implementation
 *
 * @see AfterClassLikeVisitInterface "Due to caching the AST is crawled the first time Psalm sees the file"
 * @see https://github.com/vimeo/psalm/issues/4684
 */
class DynamicallySuppressClassIssueBasedOnInterface implements AfterClassLikeVisitInterface
{
    public static function afterClassLikeVisit(
        Node\Stmt\ClassLike $stmt,
        ClassLikeStorage $storage,
        FileSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    )
    {
        if (
            $storage->user_defined &&
            !$storage->is_interface &&
            (new ReflectionClass($storage->name))->implementsInterface(\Your\Interface::class)
        ) {
            $storage->suppressed_issues[] = 'PropertyNotSetInConstructor';
        }
    }
}
