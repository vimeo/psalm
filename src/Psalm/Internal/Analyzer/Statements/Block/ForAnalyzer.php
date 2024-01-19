<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;

use function array_merge;
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

        $while_true = !$stmt->cond && !$stmt->init && !$stmt->loop;

        return LoopAnalyzer::analyzeForOrWhile(
            $statements_analyzer,
            $stmt,
            $context,
            $while_true,
            $init_var_types,
            $assigned_var_ids,
            $stmt->cond,
            $stmt->loop,
        );
    }
}
