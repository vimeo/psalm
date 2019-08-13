<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use \Psalm\Internal\Analyzer\Statements\Block\IfAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Reconciler;
use Psalm\Internal\Type\AssertionReconciler;
use function array_merge;
use function array_map;
use function array_diff_key;
use function array_filter;
use const ARRAY_FILTER_USE_KEY;
use function array_values;
use function array_keys;
use function preg_match;
use function preg_quote;
use function array_intersect_key;

/**
 * @internal
 */
class TernaryAnalyzer
{
    /**
     * @param   StatementsAnalyzer           $statements_analyzer
     * @param   PhpParser\Node\Expr\Ternary $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Ternary $stmt,
        Context $context
    ) {
        $first_if_cond_expr = IfAnalyzer::getDefinitelyEvaluatedExpression($stmt->cond);

        $was_inside_conditional = $context->inside_conditional;

        $context->inside_conditional = true;

        $pre_condition_vars_in_scope = $context->vars_in_scope;

        $referenced_var_ids = $context->referenced_var_ids;
        $context->referenced_var_ids = [];

        $pre_assigned_var_ids = $context->assigned_var_ids;
        $context->assigned_var_ids = [];

        if ($first_if_cond_expr) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $first_if_cond_expr, $context) === false) {
                return false;
            }
        }

        $first_cond_assigned_var_ids = $context->assigned_var_ids;
        $context->assigned_var_ids = array_merge(
            $pre_assigned_var_ids,
            $first_cond_assigned_var_ids
        );

        /** @var array<string, bool> */
        $first_cond_referenced_var_ids = $context->referenced_var_ids;
        $context->referenced_var_ids = array_merge(
            $referenced_var_ids,
            $first_cond_referenced_var_ids
        );

        if (!$was_inside_conditional) {
            $context->inside_conditional = false;
        }

        $t_if_context = clone $context;

        $t_if_context->inside_conditional = true;

        if ($first_if_cond_expr !== $stmt->cond) {
            $assigned_var_ids = $context->assigned_var_ids;
            $t_if_context->assigned_var_ids = [];

            $referenced_var_ids = $context->referenced_var_ids;
            $t_if_context->referenced_var_ids = [];

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->cond, $t_if_context) === false) {
                return false;
            }

            /** @var array<string, bool> */
            $more_cond_referenced_var_ids = $t_if_context->referenced_var_ids;
            $t_if_context->referenced_var_ids = array_merge(
                $more_cond_referenced_var_ids,
                $referenced_var_ids
            );

            $cond_referenced_var_ids = array_merge(
                $first_cond_referenced_var_ids,
                $more_cond_referenced_var_ids
            );

            /** @var array<string, bool> */
            $more_cond_assigned_var_ids = $t_if_context->assigned_var_ids;
            $t_if_context->assigned_var_ids = array_merge(
                $more_cond_assigned_var_ids,
                $assigned_var_ids
            );

            $cond_assigned_var_ids = array_merge(
                $first_cond_assigned_var_ids,
                $more_cond_assigned_var_ids
            );
        } else {
            $cond_referenced_var_ids = $first_cond_referenced_var_ids;

            $cond_assigned_var_ids = $first_cond_assigned_var_ids;
        }

        $newish_var_ids = array_map(
            /**
             * @param Type\Union $_
             *
             * @return true
             */
            function (Type\Union $_) {
                return true;
            },
            array_diff_key(
                $t_if_context->vars_in_scope,
                $pre_condition_vars_in_scope,
                $cond_referenced_var_ids,
                $cond_assigned_var_ids
            )
        );

        // get all the var ids that were referened in the conditional, but not assigned in it
        $cond_referenced_var_ids = array_diff_key($cond_referenced_var_ids, $cond_assigned_var_ids);

        // remove all newly-asserted var ids too
        $cond_referenced_var_ids = array_filter(
            $cond_referenced_var_ids,
            /**
             * @param string $var_id
             *
             * @return bool
             */
            function ($var_id) use ($pre_condition_vars_in_scope) {
                return isset($pre_condition_vars_in_scope[$var_id]);
            },
            ARRAY_FILTER_USE_KEY
        );

        $cond_referenced_var_ids = array_merge($newish_var_ids, $cond_referenced_var_ids);

        $t_if_context->inside_conditional = false;

        $codebase = $statements_analyzer->getCodebase();

        $if_clauses = \Psalm\Type\Algebra::getFormula(
            $stmt->cond,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
            $codebase
        );

        $mixed_var_ids = [];

        foreach ($context->vars_in_scope as $var_id => $type) {
            if ($type->hasMixed()) {
                $mixed_var_ids[] = $var_id;
            }
        }

        foreach ($context->vars_possibly_in_scope as $var_id => $_) {
            if (!isset($context->vars_in_scope[$var_id])) {
                $mixed_var_ids[] = $var_id;
            }
        }


        $if_clauses = array_values(
            array_map(
                /**
                 * @return \Psalm\Internal\Clause
                 */
                function (\Psalm\Internal\Clause $c) use ($mixed_var_ids) {
                    $keys = array_keys($c->possibilities);

                    foreach ($keys as $key) {
                        foreach ($mixed_var_ids as $mixed_var_id) {
                            if (preg_match('/^' . preg_quote($mixed_var_id, '/') . '(\[|-)/', $key)) {
                                return new \Psalm\Internal\Clause([], true);
                            }
                        }
                    }

                    return $c;
                },
                $if_clauses
            )
        );

        $ternary_clauses = Algebra::simplifyCNF(array_merge($context->clauses, $if_clauses));

        $negated_clauses = Algebra::negateFormula($if_clauses);

        $negated_if_types = Algebra::getTruthsFromFormula(
            Algebra::simplifyCNF(
                array_merge($context->clauses, $negated_clauses)
            )
        );

        $reconcilable_if_types = Algebra::getTruthsFromFormula($ternary_clauses, $cond_referenced_var_ids);

        $changed_var_ids = [];

        if ($reconcilable_if_types) {
            $t_if_vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
                $reconcilable_if_types,
                $t_if_context->vars_in_scope,
                $changed_var_ids,
                $cond_referenced_var_ids,
                $statements_analyzer,
                [],
                $t_if_context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $stmt->cond)
            );

            $t_if_context->vars_in_scope = $t_if_vars_in_scope_reconciled;
        }

        $t_else_context = clone $context;

        if ($stmt->if) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->if, $t_if_context) === false) {
                return false;
            }

            foreach ($t_if_context->vars_in_scope as $var_id => $type) {
                if (isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::combineUnionTypes($context->vars_in_scope[$var_id], $type);
                }
            }

            $context->referenced_var_ids = array_merge(
                $context->referenced_var_ids,
                $t_if_context->referenced_var_ids
            );

            $context->unreferenced_vars = array_intersect_key(
                $context->unreferenced_vars,
                $t_if_context->unreferenced_vars
            );
        }

        if ($negated_if_types) {
            $t_else_vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
                $negated_if_types,
                $t_else_context->vars_in_scope,
                $changed_var_ids,
                $cond_referenced_var_ids,
                $statements_analyzer,
                [],
                $t_else_context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $stmt->else)
            );

            $t_else_context->vars_in_scope = $t_else_vars_in_scope_reconciled;
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->else, $t_else_context) === false) {
            return false;
        }

        foreach ($t_else_context->vars_in_scope as $var_id => $type) {
            if (isset($context->vars_in_scope[$var_id])) {
                $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                    $context->vars_in_scope[$var_id],
                    $type
                );
            } elseif (isset($t_if_context->vars_in_scope[$var_id])) {
                $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                    $t_if_context->vars_in_scope[$var_id],
                    $type
                );
            }
        }

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $t_if_context->vars_possibly_in_scope,
            $t_else_context->vars_possibly_in_scope
        );

        $context->referenced_var_ids = array_merge(
            $context->referenced_var_ids,
            $t_else_context->referenced_var_ids
        );

        $context->unreferenced_vars = array_intersect_key(
            $context->unreferenced_vars,
            $t_else_context->unreferenced_vars
        );

        $lhs_type = null;

        if ($stmt->if) {
            if (isset($stmt->if->inferredType)) {
                $lhs_type = $stmt->if->inferredType;
            }
        } elseif (isset($stmt->cond->inferredType)) {
            $if_return_type_reconciled = AssertionReconciler::reconcile(
                '!falsy',
                clone $stmt->cond->inferredType,
                '',
                $statements_analyzer,
                $context->inside_loop,
                [],
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer->getSuppressedIssues()
            );

            $lhs_type = $if_return_type_reconciled;
        }

        if (!$lhs_type || !isset($stmt->else->inferredType)) {
            $stmt->inferredType = Type::getMixed();
        } else {
            $stmt->inferredType = Type::combineUnionTypes($lhs_type, $stmt->else->inferredType);
        }

        return null;
    }
}
