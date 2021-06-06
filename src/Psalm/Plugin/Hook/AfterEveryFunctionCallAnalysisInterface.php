<?php
namespace Psalm\Plugin\Hook;

use PhpParser\Node\Expr\FuncCall;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\StatementsSource;

/** @deprecated going to be removed in Psalm 5 */
interface AfterEveryFunctionCallAnalysisInterface
{
    public static function afterEveryFunctionCallAnalysis(
        FuncCall $expr,
        string $function_id,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase
    ): void;
}
