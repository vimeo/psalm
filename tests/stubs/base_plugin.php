<?php

class BasePlugin extends \Psalm\Plugin
{
    public static function afterFunctionCallAnalysis(
        \PhpParser\Node\Expr\FuncCall $expr,
        string $function_id,
        \Psalm\Context $context,
        \Psalm\StatementsSource $statements_source,
        \Psalm\Codebase $codebase,
        array &$file_replacements = [],
        \Psalm\Type\Union &$return_type_candidate = null
    ) {
    }
}
