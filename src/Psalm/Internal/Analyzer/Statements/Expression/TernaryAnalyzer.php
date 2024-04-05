<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\ComplicatedExpressionException;
use Psalm\Exception\ScopeAnalysisException;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\AlgebraAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfConditionalAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Internal\Scope\IfScope;
use Psalm\Internal\Type\AssertionReconciler;
use Psalm\Node\Expr\VirtualBooleanNot;
use Psalm\Storage\Assertion\Truthy;
use Psalm\Type;
use Psalm\Type\Reconciler;

use function array_diff;
use function array_filter;
use function array_intersect;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function in_array;
use function preg_match;
use function preg_quote;
use function spl_object_id;

/**
 * @internal
 */
final class TernaryAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Ternary $stmt,
        Context $context
    ): bool {
        $codebase = $statements_analyzer->getCodebase();

        $if_scope = new IfScope();

        try {
            $if_conditional_scope = IfConditionalAnalyzer::analyze(
                $statements_analyzer,
                $stmt->cond,
                $context,
                $codebase,
                $if_scope,
                $context->branch_point ?: (int) $stmt->getAttribute('startFilePos'),
            );

            // this is the context for stuff that happens within the first operand of the ternary
            $if_context = $if_conditional_scope->if_context;

            $cond_referenced_var_ids = $if_conditional_scope->cond_referenced_var_ids;
            $assigned_in_conditional_var_ids = $if_conditional_scope->assigned_in_conditional_var_ids;
        } catch (ScopeAnalysisException $e) {
            return false;
        }

        $cond_object_id = spl_object_id($stmt->cond);

        $if_clauses = FormulaGenerator::getFormula(
            $cond_object_id,
            $cond_object_id,
            $stmt->cond,
            $context->self,
            $statements_analyzer,
            $codebase,
        );

        if (count($if_clauses) > 200) {
            $if_clauses = [];
        }

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

        $if_clauses = array_map(
            static function (Clause $c) use ($mixed_var_ids, $cond_object_id): Clause {
                $keys = array_keys($c->possibilities);

                $mixed_var_ids = array_diff($mixed_var_ids, $keys);

                foreach ($keys as $key) {
                    foreach ($mixed_var_ids as $mixed_var_id) {
                        if (preg_match('/^' . preg_quote($mixed_var_id, '/') . '(\[|-)/', $key)) {
                            return new Clause([], $cond_object_id, $cond_object_id, true);
                        }
                    }
                }

                return $c;
            },
            $if_clauses,
        );

        $entry_clauses = $context->clauses;

        // this will see whether any of the clauses in set A conflict with the clauses in set B
        AlgebraAnalyzer::checkForParadox(
            $context->clauses,
            $if_clauses,
            $statements_analyzer,
            $stmt->cond,
            $assigned_in_conditional_var_ids,
        );

        $if_clauses = Algebra::simplifyCNF($if_clauses);

        $ternary_context_clauses = $entry_clauses
            ? Algebra::simplifyCNF([...$entry_clauses, ...$if_clauses])
            : $if_clauses;

        if ($if_context->reconciled_expression_clauses) {
            $reconciled_expression_clauses = $if_context->reconciled_expression_clauses;

            $ternary_context_clauses = array_values(
                array_filter(
                    $ternary_context_clauses,
                    static fn(Clause $c): bool => !in_array($c->hash, $reconciled_expression_clauses),
                ),
            );

            if (count($if_context->clauses) === 1
                && $if_context->clauses[0]->wedge
                && !$if_context->clauses[0]->possibilities
            ) {
                $if_context->clauses = [];
                $if_context->reconciled_expression_clauses = [];
            }
        }

        try {
            $if_scope->negated_clauses = Algebra::negateFormula($if_clauses);
        } catch (ComplicatedExpressionException $e) {
            try {
                $if_scope->negated_clauses = FormulaGenerator::getFormula(
                    $cond_object_id,
                    $cond_object_id,
                    new VirtualBooleanNot($stmt->cond),
                    $context->self,
                    $statements_analyzer,
                    $codebase,
                    false,
                );
            } catch (ComplicatedExpressionException $e) {
                $if_scope->negated_clauses = [];
            }
        }

        $if_scope->negated_types = Algebra::getTruthsFromFormula(
            Algebra::simplifyCNF(
                [...$context->clauses, ...$if_scope->negated_clauses],
            ),
        );

        $active_if_types = [];

        $reconcilable_if_types = Algebra::getTruthsFromFormula(
            $ternary_context_clauses,
            $cond_object_id,
            $cond_referenced_var_ids,
            $active_if_types,
        );

        $changed_var_ids = [];

        if ($reconcilable_if_types) {
            [$if_context->vars_in_scope, $if_context->references_in_scope] = Reconciler::reconcileKeyedTypes(
                $reconcilable_if_types,
                $active_if_types,
                $if_context->vars_in_scope,
                $if_context->references_in_scope,
                $changed_var_ids,
                $cond_referenced_var_ids,
                $statements_analyzer,
                $statements_analyzer->getTemplateTypeMap() ?: [],
                $if_context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $stmt->cond),
            );
        }

        $t_else_context = clone $context;

        if ($stmt->if) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->if, $if_context) === false) {
                return false;
            }

            $context->cond_referenced_var_ids = array_merge(
                $context->cond_referenced_var_ids,
                $if_context->cond_referenced_var_ids,
            );
        }

        $t_else_context->clauses = Algebra::simplifyCNF(
            [...$t_else_context->clauses, ...$if_scope->negated_clauses],
        );

        $changed_var_ids = [];

        if ($if_scope->negated_types) {
            [$t_else_context->vars_in_scope, $t_else_context->references_in_scope] = Reconciler::reconcileKeyedTypes(
                $if_scope->negated_types,
                $if_scope->negated_types,
                $t_else_context->vars_in_scope,
                $t_else_context->references_in_scope,
                $changed_var_ids,
                $cond_referenced_var_ids,
                $statements_analyzer,
                $statements_analyzer->getTemplateTypeMap() ?: [],
                $t_else_context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $stmt->else),
            );

            $t_else_context->clauses = Context::removeReconciledClauses($t_else_context->clauses, $changed_var_ids)[0];
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->else, $t_else_context) === false) {
            return false;
        }

        $assign_var_ifs = $if_context->assigned_var_ids;
        $assign_var_else = $t_else_context->assigned_var_ids;
        $assign_all = array_intersect_key($assign_var_ifs, $assign_var_else);

        //if the same var was assigned in both branches
        foreach ($assign_all as $var_id => $_) {
            if (isset($if_context->vars_in_scope[$var_id]) && isset($t_else_context->vars_in_scope[$var_id])) {
                $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                    $if_context->vars_in_scope[$var_id],
                    $t_else_context->vars_in_scope[$var_id],
                );
            }
        }

        $redef_var_ifs = array_keys($if_context->getRedefinedVars($context->vars_in_scope));
        $redef_var_else = array_keys($t_else_context->getRedefinedVars($context->vars_in_scope));
        $redef_all = array_intersect($redef_var_ifs, $redef_var_else);

        //these vars were changed in both branches
        foreach ($redef_all as $redef_var_id) {
            $context->vars_in_scope[$redef_var_id] = Type::combineUnionTypes(
                $if_context->vars_in_scope[$redef_var_id],
                $t_else_context->vars_in_scope[$redef_var_id],
            );
        }

        //these vars were changed in the if and existed before
        foreach ($redef_var_ifs as $redef_var_ifs_id) {
            if (isset($context->vars_in_scope[$redef_var_ifs_id])) {
                $context->vars_in_scope[$redef_var_ifs_id] = Type::combineUnionTypes(
                    $context->vars_in_scope[$redef_var_ifs_id],
                    $if_context->vars_in_scope[$redef_var_ifs_id],
                );
            }
        }

        //these vars were changed in the else and existed before
        foreach ($redef_var_else as $redef_var_else_id) {
            if (isset($context->vars_in_scope[$redef_var_else_id])) {
                $context->vars_in_scope[$redef_var_else_id] = Type::combineUnionTypes(
                    $context->vars_in_scope[$redef_var_else_id],
                    $t_else_context->vars_in_scope[$redef_var_else_id],
                );
            }
        }

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $if_context->vars_possibly_in_scope,
            $t_else_context->vars_possibly_in_scope,
        );

        $context->cond_referenced_var_ids = array_merge(
            $context->cond_referenced_var_ids,
            $t_else_context->cond_referenced_var_ids,
        );

        $lhs_type = null;
        $stmt_cond_type = $statements_analyzer->node_data->getType($stmt->cond);
        if ($stmt->if) {
            if ($stmt_if_type = $statements_analyzer->node_data->getType($stmt->if)) {
                $lhs_type = $stmt_if_type;
            }
        } elseif ($stmt_cond_type) {
            $if_return_type_reconciled = AssertionReconciler::reconcile(
                new Truthy(),
                $stmt_cond_type,
                '',
                $statements_analyzer,
                $context->inside_loop,
                [],
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer->getSuppressedIssues(),
            );

            $lhs_type = $if_return_type_reconciled;
        }

        if ($lhs_type && ($stmt_else_type = $statements_analyzer->node_data->getType($stmt->else))) {
            if ($stmt_cond_type !== null && $stmt_cond_type->isAlwaysFalsy()) {
                $statements_analyzer->node_data->setType($stmt, $stmt_else_type);
            } elseif ($stmt_cond_type !== null && $stmt_cond_type->isAlwaysTruthy()) {
                $statements_analyzer->node_data->setType($stmt, $lhs_type);
            } else {
                $statements_analyzer->node_data->setType($stmt, Type::combineUnionTypes($lhs_type, $stmt_else_type));
            }
        } else {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
        }

        return true;
    }
}
