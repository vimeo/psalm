<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Node\Expr\VirtualIsset;
use Psalm\Node\Expr\VirtualTernary;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Storage\Assertion\IsIsset;
use Psalm\Type;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Reconciler;
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
        Context $context,
    ): bool {
        $left_expr = $stmt->left;

        $root_expr = $left_expr;

        while ($root_expr instanceof PhpParser\Node\Expr\ArrayDimFetch
            || $root_expr instanceof PhpParser\Node\Expr\PropertyFetch
        ) {
            $root_expr = $root_expr->var;
        }

        $has_call_root = $root_expr instanceof PhpParser\Node\Expr\FuncCall
            || $root_expr instanceof PhpParser\Node\Expr\MethodCall
            || $root_expr instanceof PhpParser\Node\Expr\StaticCall
            || $root_expr instanceof PhpParser\Node\Expr\Cast
            || $root_expr instanceof PhpParser\Node\Expr\Match_
            || $root_expr instanceof PhpParser\Node\Expr\NullsafePropertyFetch
            || $root_expr instanceof PhpParser\Node\Expr\NullsafeMethodCall
            || $root_expr instanceof PhpParser\Node\Expr\Ternary;

        $has_untracked_offset = !$has_call_root
            && $left_expr instanceof PhpParser\Node\Expr\ArrayDimFetch
            && ExpressionIdentifier::getExtendedVarId(
                $left_expr,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer,
            ) === null;

        if ($has_call_root || $has_untracked_offset) {
            $left_var_id = '$<tmp coalesce var>' . (int) $left_expr->getAttribute('startFilePos');

            // an untracked offset is evaluated unconditionally, so its assignments belong to the real context
            $cloned = $has_untracked_offset ? $context : clone $context;
            $was_inside_isset = $cloned->inside_isset;
            $cloned->inside_isset = true;

            ExpressionAnalyzer::analyze($statements_analyzer, $left_expr, $cloned);

            if ($has_untracked_offset) {
                $context->inside_isset = $was_inside_isset;
            } else {
                // the call is evaluated unconditionally: what it assigns is set on the right too
                foreach ($cloned->assigned_var_ids as $assigned_var_id => $assigned_offset) {
                    if (($context->assigned_var_ids[$assigned_var_id] ?? null) !== $assigned_offset
                        && isset($cloned->vars_in_scope[$assigned_var_id])
                    ) {
                        $context->vars_in_scope[$assigned_var_id] = $cloned->vars_in_scope[$assigned_var_id];
                        $context->assigned_var_ids[$assigned_var_id] = $assigned_offset;
                        $context->vars_possibly_in_scope[$assigned_var_id] = true;
                    }
                }
            }

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

        // `$a ?? throw …` ends unless $a is set, so it is set afterwards
        if ($stmt->right instanceof PhpParser\Node\Expr\Throw_) {
            $coalesced_var_id = ExpressionIdentifier::getExtendedVarId(
                $stmt->left,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer,
            );

            if ($coalesced_var_id !== null && isset($context->vars_in_scope[$coalesced_var_id])) {
                $changed_var_ids = [];

                [$context->vars_in_scope, $context->references_in_scope] = Reconciler::reconcileKeyedTypes(
                    [$coalesced_var_id => [[new IsIsset()]]],
                    [],
                    $context->vars_in_scope,
                    $context->references_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    [],
                    $context->inside_loop,
                );

                foreach ($changed_var_ids as $changed_var_id => $_) {
                    $context->removeVarFromConflictingClauses($changed_var_id);
                }
            }
        }

        return true;
    }
}
