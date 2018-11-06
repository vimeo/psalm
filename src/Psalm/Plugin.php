<?php
namespace Psalm;

use PhpParser;
use Psalm\FileManipulation\FileManipulation;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Union;

abstract class Plugin
{
    /**
     * Called after an expression has been checked
     *
     * @param  PhpParser\Node\Expr  $expr
     * @param  Context              $context
     * @param  StatementsSource           $file_soure
     * @param  string[]             $suppressed_issues
     * @param  FileManipulation[]   $file_replacements
     *
     * @return null|false
     */
    public static function afterExpressionAnalysis(
        PhpParser\Node\Expr $expr,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ) {
        return null;
    }

    /**
     * Called after a statement has been checked
     *
     * @param  string[]             $suppressed_issues
     * @param  FileManipulation[]   $file_replacements
     *
     * @return null|false
     */
    public static function afterStatementAnalysis(
        PhpParser\Node\Stmt $stmt,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ) {
        return null;
    }

    /**
     * @param  FileManipulation[] $file_replacements
     *
     * @return void
     */
    public static function afterClassLikeVisit(
        PhpParser\Node\Stmt\ClassLike $stmt,
        ClassLikeStorage $storage,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ) {
    }

    /**
     * @param  string             $fq_class_name
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
    ) {
    }

    /**
     * @param  PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $expr
     * @param  string $method_id - the method id being checked
     * @param  string $appearing_method_id - the method id of the class that the method appears in
     * @param  string $declaring_method_id - the method id of the class or trait that declares the method
     * @param  string|null $var_id - a reference to the LHS of the variable
     * @param  PhpParser\Node\Arg[] $args
     * @param  FileManipulation[] $file_replacements
     *
     * @return void
     */
    public static function afterMethodCallAnalysis(
        PhpParser\Node\Expr $expr,
        string $method_id,
        string $appearing_method_id,
        string $declaring_method_id,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = [],
        Union $return_type_candidate = null
    ) {
    }

    /**
     * @param  string $function_id - the method id being checked
     * @param  PhpParser\Node\Arg[] $args
     * @param  FileManipulation[] $file_replacements
     *
     * @return void
     */
    public static function afterFunctionCallAnalysis(
        PhpParser\Node\Expr\FuncCall $expr,
        string $function_id,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = [],
        Union &$return_type_candidate = null
    ) {
    }
}
