<?php

class BasePlugin implements Psalm\Plugin\Hook\AfterFunctionCallAnalysisInterface
{
    public static function afterFunctionCallAnalysis(
        \PhpParser\Node\Expr\FuncCall $expr,
        string $function_id,
        \Psalm\Context $context,
        \Psalm\StatementsSource $statements_source,
        \Psalm\Codebase $codebase,
        \Psalm\Type\Union $return_type_candidate,
        array &$file_replacements
    ): void {
    }
}
