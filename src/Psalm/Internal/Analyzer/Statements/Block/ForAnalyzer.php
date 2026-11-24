<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;

use function array_merge;
use function array_slice;
use function count;
use function is_string;

/**
 * @internal
 */
final class ForAnalyzer
{
    /**
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\For_ $stmt,
        Context $context,
    ): ?bool {
        $pre_assigned_var_ids = $context->assigned_var_ids;
        $context->assigned_var_ids = [];

        $init_var_types = [];

        foreach ($stmt->init as $init) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $init, $context) === false) {
                return false;
            }

            if ($init instanceof PhpParser\Node\Expr\Assign
                && $init->var instanceof PhpParser\Node\Expr\Variable
                && is_string($init->var->name)
                && ($init_var_type = $statements_analyzer->node_data->getType($init->expr))
            ) {
                $init_var_types[$init->var->name] = $init_var_type;
            }
        }

        $assigned_var_ids = $context->assigned_var_ids;

        $context->assigned_var_ids = array_merge(
            $pre_assigned_var_ids,
            $assigned_var_ids,
        );

        // the last condition decides whether the loop goes on
        $while_true = !$stmt->cond
            || LoopAnalyzer::isAlwaysTruthyCondition($statements_analyzer, $stmt->cond[count($stmt->cond) - 1]);

        // the operands of the `&&` of the last condition are analysed one after the other, like those of a while
        $and_operands = $stmt->cond ? WhileAnalyzer::getAndExpressions($stmt->cond[count($stmt->cond) - 1]) : [];

        if (LoopAnalyzer::analyzeForOrWhile(
            $statements_analyzer,
            $stmt,
            $context,
            $while_true,
            $init_var_types,
            $assigned_var_ids,
            [...array_slice($stmt->cond, 0, -1), ...$and_operands],
            $stmt->loop,
        ) === false) {
            return false;
        }

        // with the types of the last pass, as the `&&` would check them
        if (count($and_operands) > 1) {
            foreach ($and_operands as $and_operand) {
                IfConditionalAnalyzer::handleParadoxicalCondition($statements_analyzer, $and_operand);
            }
        }

        return null;
    }
}
