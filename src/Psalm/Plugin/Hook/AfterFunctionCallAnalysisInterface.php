<?php
namespace Psalm\Plugin\Hook;

use PhpParser\Node\Expr\FuncCall;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\StatementsSource;
use Psalm\Type\Union;

interface AfterFunctionCallAnalysisInterface
{
    /**
     * @param  FileManipulation[] $file_replacements
     *
     * @return void
     */
    public static function afterFunctionCallAnalysis(
        FuncCall $expr,
        string $function_id,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = [],
        Union &$return_type_candidate = null
    );
}
