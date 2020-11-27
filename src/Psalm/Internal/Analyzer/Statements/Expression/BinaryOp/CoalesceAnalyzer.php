<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Context;
use Psalm\Type;
use function substr;

/**
 * @internal
 */
class CoalesceAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp\Coalesce $stmt,
        Context $context
    ) : bool {
        $left_expr = $stmt->left;

        if ($left_expr instanceof PhpParser\Node\Expr\FuncCall
            || $left_expr instanceof PhpParser\Node\Expr\MethodCall
            || $left_expr instanceof PhpParser\Node\Expr\StaticCall
            || $left_expr instanceof PhpParser\Node\Expr\Cast
        ) {
            $left_var_id = '$<tmp coalesce var>' . (int) $left_expr->getAttribute('startFilePos');

            ExpressionAnalyzer::analyze($statements_analyzer, $left_expr, clone $context);

            $condition_type = $statements_analyzer->node_data->getType($left_expr) ?: Type::getMixed();

            $context->vars_in_scope[$left_var_id] = $condition_type;

            $left_expr = new PhpParser\Node\Expr\Variable(
                substr($left_var_id, 1),
                $left_expr->getAttributes()
            );
        }

        $ternary = new PhpParser\Node\Expr\Ternary(
            new PhpParser\Node\Expr\Isset_(
                [$left_expr],
                $stmt->left->getAttributes()
            ),
            $left_expr,
            $stmt->right,
            $stmt->getAttributes()
        );

        $old_node_data = $statements_analyzer->node_data;

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        ExpressionAnalyzer::analyze($statements_analyzer, $ternary, clone $context);

        $ternary_type = $statements_analyzer->node_data->getType($ternary) ?: Type::getMixed();

        $statements_analyzer->node_data = $old_node_data;

        $statements_analyzer->node_data->setType($stmt, $ternary_type);

        return true;
    }
}
