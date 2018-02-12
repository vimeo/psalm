<?php
namespace Psalm;

use PhpParser;
use Psalm\Checker\StatementsChecker;
use Psalm\FileManipulation\FileManipulation;
use Psalm\Scanner\FileScanner;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Union;

abstract class Plugin
{
    /**
     * Called after an expression has been checked
     *
     * @param  StatementsChecker    $statements_checker
     * @param  PhpParser\Node\Expr  $stmt
     * @param  Context              $context
     * @param  CodeLocation         $code_location
     * @param  string[]             $suppressed_issues
     * @param  FileManipulation[]   $file_replacements
     *
     * @return null|false
     */
    public static function afterExpressionCheck(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $stmt,
        Context $context,
        CodeLocation $code_location,
        array $suppressed_issues,
        array &$file_replacements = []
    ) {
        return null;
    }

    /**
     * Called after a statement has been checked
     *
     * @param  StatementsChecker                        $statements_checker
     * @param  PhpParser\Node\Stmt|PhpParser\Node\Expr  $stmt
     * @param  Context                                  $context
     * @param  CodeLocation                             $code_location
     * @param  string[]                                 $suppressed_issues
     * @param  FileManipulation[]                       $file_replacements
     *
     * @return null|false
     */
    public static function afterStatementCheck(
        StatementsChecker $statements_checker,
        PhpParser\Node $stmt,
        Context $context,
        CodeLocation $code_location,
        array $suppressed_issues,
        array &$file_replacements = []
    ) {
        return null;
    }

    /**
     * @param  FileManipulation[] $file_replacements
     *
     * @return void
     */
    public static function afterVisitClassLike(
        PhpParser\Node\Stmt\ClassLike $stmt,
        ClassLikeStorage $storage,
        FileScanner $file,
        Aliases $aliases,
        array &$file_replacements = []
    ) {
    }

    /**
     * @param  string             $fq_class_name
     * @param  FileManipulation[] $file_replacements
     *
     * @return void
     */
    public static function afterClassLikeExistsCheck(
        StatementsSource $statements_source,
        $fq_class_name,
        CodeLocation $code_location,
        array &$file_replacements = []
    ) {
    }

    /**
     * @param  string $method_id - the method id being checked
     * @param  string $appearing_method_id - the method id of the class that contains the method
     * @param  string $declaring_method_id - the method id of the class or trait that declares the method
     * @param  PhpParser\Node\Arg[] $args
     * @param  FileManipulation[] $file_replacements
     *
     * @return void
     */
    public static function afterMethodCallCheck(
        StatementsSource $statements_source,
        $method_id,
        $declaring_method_id,
        array $args,
        CodeLocation $code_location,
        array &$file_replacements = [],
        Union &$return_type_candidate = null
    ) {
    }
}
