<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Node\Expr\VirtualIsset;
use Psalm\Node\Expr\VirtualTernary;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Type;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Union;

use function substr;

/**
 * @internal
 */
final class CoalesceAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp\Coalesce $stmt,
        Context $context
    ): bool {
        $left_expr = $stmt->left;

        $root_expr = $left_expr;

        while ($root_expr instanceof PhpParser\Node\Expr\ArrayDimFetch
            || $root_expr instanceof PhpParser\Node\Expr\PropertyFetch
        ) {
            $root_expr = $root_expr->var;
        }

        if ($root_expr instanceof PhpParser\Node\Expr\FuncCall
            || $root_expr instanceof PhpParser\Node\Expr\MethodCall
            || $root_expr instanceof PhpParser\Node\Expr\StaticCall
            || $root_expr instanceof PhpParser\Node\Expr\Cast
            || $root_expr instanceof PhpParser\Node\Expr\Match_
            || $root_expr instanceof PhpParser\Node\Expr\NullsafePropertyFetch
            || $root_expr instanceof PhpParser\Node\Expr\NullsafeMethodCall
            || $root_expr instanceof PhpParser\Node\Expr\Ternary
        ) {
            $left_var_id = '$<tmp coalesce var>' . (int) $left_expr->getAttribute('startFilePos');

            $cloned = clone $context;
            $cloned->inside_isset = true;

            ExpressionAnalyzer::analyze($statements_analyzer, $left_expr, $cloned);

            if ($root_expr !== $left_expr) {
                $condition_type = $statements_analyzer->node_data->getType($left_expr);
                if ($condition_type) {
                    $condition_type = $condition_type->setPossiblyUndefined(true);
                } else {
                    $condition_type = new Union([new TMixed()], ['possibly_undefined' => true]);
                }
            } else {
                $condition_type = $statements_analyzer->node_data->getType($left_expr) ?? Type::getMixed();
            }

            $context->vars_in_scope[$left_var_id] = $condition_type;

            $left_expr = new VirtualVariable(
                substr($left_var_id, 1),
                $left_expr->getAttributes(),
            );
        }

        $ternary = new VirtualTernary(
            new VirtualIsset(
                [$left_expr],
                $stmt->left->getAttributes(),
            ),
            $left_expr,
            $stmt->right,
            $stmt->getAttributes(),
        );

        $old_node_data = $statements_analyzer->node_data;

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        ExpressionAnalyzer::analyze($statements_analyzer, $ternary, $context);

        $ternary_type = $statements_analyzer->node_data->getType($ternary) ?? Type::getMixed();

        $statements_analyzer->node_data = $old_node_data;

        $statements_analyzer->node_data->setType($stmt, $ternary_type);

        return true;
    }
}
