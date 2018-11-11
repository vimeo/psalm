<?php
namespace Psalm;

use PhpParser;
use Psalm\FileManipulation\FileManipulation;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Union;
use Psalm\PluginApi\Hook;

abstract class Plugin implements
    Hook\AfterExpressionAnalysisInterface,
    Hook\AfterStatementAnalysisInterface,
    Hook\AfterClassLikeVisitInterface,
    Hook\AfterClassLikeExistenceCheckInterface,
    Hook\AfterMethodCallAnalysisInterface,
    Hook\AfterFunctionCallAnalysisInterface
{
    public static function afterExpressionAnalysis(
        PhpParser\Node\Expr $expr,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ) {
        return null;
    }

    public static function afterStatementAnalysis(
        PhpParser\Node\Stmt $stmt,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ) {
        return null;
    }

    public static function afterClassLikeVisit(
        PhpParser\Node\Stmt\ClassLike $stmt,
        ClassLikeStorage $storage,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ) {
    }

    public static function afterClassLikeExistenceCheck(
        string $fq_class_name,
        CodeLocation $code_location,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ) {
    }

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
