<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Type;

/**
 * @internal
 */
final class YieldFromAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\YieldFrom $stmt,
        Context $context,
    ): bool {
        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            $context->inside_call = $was_inside_call;

            return false;
        }

        if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
            $key_type = null;
            $value_type = null;
            $always_non_empty_array = true;
            if (ForeachAnalyzer::checkIteratorType(
                $statements_analyzer,
                $stmt,
                $stmt->expr,
                $stmt_expr_type,
                $statements_analyzer->getCodebase(),
                $context,
                $key_type,
                $value_type,
                $always_non_empty_array,
            ) === false
            ) {
                $context->inside_call = $was_inside_call;

                return false;
            }

            $statements_analyzer->node_data->setType($stmt, Type::getNull());
        }

        $context->inside_call = $was_inside_call;

        return true;
    }
}
