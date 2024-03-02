<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Type;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

/**
 * @internal
 */
final class BooleanNotAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BooleanNot $stmt,
        Context $context
    ): bool {


        $inside_negation = $context->inside_negation;

        $context->inside_negation = !$inside_negation;

        $result = ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context);

        $context->inside_negation = $inside_negation;

        $expr_type = $statements_analyzer->node_data->getType($stmt->expr);

        if ($expr_type) {
            if ($expr_type->isAlwaysTruthy()) {
                $stmt_type = new TFalse($expr_type->from_docblock);
            } elseif ($expr_type->isAlwaysFalsy()) {
                $stmt_type = new TTrue($expr_type->from_docblock);
            } else {
                ExpressionAnalyzer::checkRiskyTruthyFalsyComparison($expr_type, $statements_analyzer, $stmt);
                $stmt_type = new TBool();
            }

            $stmt_type = new Union([$stmt_type], [
                'parent_nodes' => $expr_type->parent_nodes,
            ]);
        } else {
            $stmt_type = Type::getBool();
        }

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        return $result;
    }
}
