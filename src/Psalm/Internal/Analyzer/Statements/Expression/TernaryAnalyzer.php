<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\ScopeAnalysisException;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\AlgebraAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfConditionalAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfElse\IfAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Scope\IfScope;
use Psalm\Internal\Type\AssertionReconciler;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Node\Stmt\VirtualExpression;
use Psalm\Storage\Assertion\Truthy;
use Psalm\Type;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function array_diff_assoc;
use function array_fill_keys;
use function array_filter;
use function array_intersect;
use function array_intersect_key;
use function array_keys;
use function spl_object_id;

/**
 * @internal
 */
final class TernaryAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Ternary $stmt,
        Context $context,
    ): bool {
        $codebase = $statements_analyzer->getCodebase();

        $if_scope = new IfScope();

        $pre_cond_clauses = $context->clauses;
        $pre_cond_reconciled_clauses = $context->reconciled_expression_clauses;
        $pre_cond_remove_vars = $context->parent_remove_vars;

        try {
            // the else of an `||` is analysed again from where the condition started, not from where it ended
            $pre_condition_context = $stmt->cond instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                || $stmt->cond instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
                    ? clone $context
                    : null;

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
        } catch (ScopeAnalysisException) {
            return false;
        }

        $reassigned_var_ids = FormulaGenerator::getVarsAssignedByOredRightSides(
            $stmt->cond,
            $context->self,
            $statements_analyzer->node_data,
        );

        IfConditionalAnalyzer::applyConditionClauses(
            $statements_analyzer,
            $stmt->cond,
            $context,
            $if_context,
            $if_scope,
            $codebase,
            $pre_cond_clauses,
            $pre_cond_reconciled_clauses,
            $pre_cond_remove_vars,
            $assigned_in_conditional_var_ids,
        );

        $active_if_types = [];

        $reconcilable_if_types = Algebra::getTruthsFromFormula(
            $if_context->clauses,
            spl_object_id($stmt->cond),
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

            if ($changed_var_ids) {
                $if_context->clauses = AlgebraAnalyzer::removeReconciledClauses(
                    $if_context->clauses,
                    $changed_var_ids,
                    $if_context->vars_in_scope,
                    $statements_analyzer,
                )[0];
            }
        }

        $if_start_vars = $if_context->vars_in_scope;
        $if_start_assigned_var_ids = $if_context->assigned_var_ids;

        $t_else_context = clone $context;

        // the else runs when every operand of an `||` was evaluated and falsy, so what they assigned is known there
        $else_rederived_var_ids = array_fill_keys(array_keys($reassigned_var_ids), 1)
            + array_filter($assigned_in_conditional_var_ids);

        if ($else_rederived_var_ids !== [] && $pre_condition_context !== null) {
            IfAnalyzer::addConditionallyAssignedVarsToContext(
                $statements_analyzer,
                $stmt->cond,
                $pre_condition_context,
                $t_else_context,
                $else_rederived_var_ids,
            );
        }

        if ($stmt->if) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->if, $if_context) === false) {
                return false;
            }

            $context->cond_referenced_var_ids = [
                ...$context->cond_referenced_var_ids,
                ...$if_context->cond_referenced_var_ids,
            ];
        }

        $t_else_context->clauses = Algebra::simplifyCNF(
            [...$t_else_context->clauses, ...$if_scope->negated_clauses],
        );

        $changed_var_ids = [];

        if ($if_scope->negated_types) {
            [$t_else_context->vars_in_scope, $t_else_context->references_in_scope] = Reconciler::reconcileKeyedTypes(
                $if_scope->negated_types,
                $if_scope->active_negated_types,
                $t_else_context->vars_in_scope,
                $t_else_context->references_in_scope,
                $changed_var_ids,
                $cond_referenced_var_ids,
                $statements_analyzer,
                $statements_analyzer->getTemplateTypeMap() ?: [],
                $t_else_context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $stmt->else),
            );

            $t_else_context->clauses = AlgebraAnalyzer::removeReconciledClauses(
                $t_else_context->clauses,
                $changed_var_ids,
                $t_else_context->vars_in_scope,
                $statements_analyzer,
            )[0];
        }

        $else_start_vars = $t_else_context->vars_in_scope;
        $else_start_assigned_var_ids = $t_else_context->assigned_var_ids;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->else, $t_else_context) === false) {
            return false;
        }

        // a branch that never finishes (throw, exit) leaves the other one as the only way past the ternary
        $if_type = $stmt->if ? $statements_analyzer->node_data->getType($stmt->if) : null;
        $else_type = $statements_analyzer->node_data->getType($stmt->else);
        $if_exits = $if_type !== null && $if_type->isNever();
        $else_exits = $else_type !== null && $else_type->isNever();

        if (!$if_exits) {
            $context->removeClausesOfVarsChangedIn($if_context);
        }

        if (!$else_exits) {
            $context->removeClausesOfVarsChangedIn($t_else_context);
        }

        if ($if_exits !== $else_exits) {
            $completing_context = $if_exits ? $t_else_context : $if_context;

            foreach ($completing_context->getRedefinedVars($context->vars_in_scope) as $var_id => $type) {
                $context->vars_in_scope[$var_id] = $type;
            }

            foreach ($completing_context->assigned_var_ids as $var_id => $assigned_at) {
                if (isset($completing_context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = $completing_context->vars_in_scope[$var_id];
                    // an enclosing ternary needs to know the variable is assigned wherever this one completes
                    $context->assigned_var_ids[$var_id] = $assigned_at;
                }
            }

            $context->vars_possibly_in_scope = [
                ...$context->vars_possibly_in_scope,
                ...$completing_context->vars_possibly_in_scope,
            ];

            $context->null_array_offsets_read = $completing_context->null_array_offsets_read;

            $context->cond_referenced_var_ids = [
                ...$context->cond_referenced_var_ids,
                ...$completing_context->cond_referenced_var_ids,
            ];

            $context->keepReferencesPossiblyBoundIn($completing_context);

            return self::setResultType($statements_analyzer, $stmt, $context);
        }

        $context->detachReferencesReboundIn($if_context, $statements_analyzer, $stmt->if ? [$stmt->if] : []);
        $context->detachReferencesReboundIn($t_else_context, $statements_analyzer, [$stmt->else]);

        $context->mergeConstraintsOfReferencesReboundIn($if_context, $stmt->if ? [$stmt->if] : [], $codebase);
        $context->mergeConstraintsOfReferencesReboundIn($t_else_context, [$stmt->else], $codebase);

        $context->keepReferencesPossiblyBoundIn($if_context);
        $context->keepReferencesPossiblyBoundIn($t_else_context);

        // the branches start with the assignments made before the ternary
        $assign_var_ifs = array_diff_assoc($if_context->assigned_var_ids, $context->assigned_var_ids);
        $assign_var_else = array_diff_assoc($t_else_context->assigned_var_ids, $context->assigned_var_ids);
        $assign_all = array_intersect_key($assign_var_ifs, $assign_var_else);

        //if the same var was assigned in both branches
        foreach ($assign_all as $var_id => $_) {
            if (isset($if_context->vars_in_scope[$var_id])
                && isset($t_else_context->vars_in_scope[$var_id])
                && !(self::keepsOuterMixedType(
                    $statements_analyzer,
                    $context,
                    $if_context,
                    $var_id,
                    $stmt->if,
                    $assigned_in_conditional_var_ids,
                ) && self::keepsOuterMixedType(
                    $statements_analyzer,
                    $context,
                    $t_else_context,
                    $var_id,
                    $stmt->else,
                    $assigned_in_conditional_var_ids + $else_rederived_var_ids,
                ))
            ) {
                $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                    $if_context->vars_in_scope[$var_id],
                    $t_else_context->vars_in_scope[$var_id],
                );

                // an enclosing ternary needs to know the variable is assigned in both of its branches too
                $context->assigned_var_ids[$var_id] = $if_context->assigned_var_ids[$var_id];
            }
        }

        self::mergeRedefinedVars(
            $statements_analyzer,
            $context,
            $if_context,
            $t_else_context,
            $if_start_vars,
            $if_start_assigned_var_ids,
            $else_start_vars,
            $else_start_assigned_var_ids,
            $assigned_in_conditional_var_ids,
            $assigned_in_conditional_var_ids + $else_rederived_var_ids,
            $stmt,
        );

        $context->vars_possibly_in_scope = [
            ...$context->vars_possibly_in_scope,
            ...$if_context->vars_possibly_in_scope,
            ...$t_else_context->vars_possibly_in_scope,
        ];

        // every way past the ternary goes through one of its branches
        $context->null_array_offsets_read = array_intersect_key(
            $if_context->null_array_offsets_read,
            $t_else_context->null_array_offsets_read,
        );

        $context->cond_referenced_var_ids = [
            ...$context->cond_referenced_var_ids,
            ...$t_else_context->cond_referenced_var_ids,
        ];

        return self::setResultType($statements_analyzer, $stmt, $context);
    }

    /**
     * Merges into the outer context the variables that the branches changed
     *
     * @param array<string, Union> $if_start_vars
     * @param array<string, int> $if_start_assigned_var_ids
     * @param array<string, Union> $else_start_vars
     * @param array<string, int> $else_start_assigned_var_ids
     * @param array<string, mixed> $if_condition_assigned_var_ids
     * @param array<string, mixed> $else_condition_assigned_var_ids
     */
    private static function mergeRedefinedVars(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        Context $if_context,
        Context $t_else_context,
        array $if_start_vars,
        array $if_start_assigned_var_ids,
        array $else_start_vars,
        array $else_start_assigned_var_ids,
        array $if_condition_assigned_var_ids,
        array $else_condition_assigned_var_ids,
        PhpParser\Node\Expr\Ternary $stmt,
    ): void {
        $redef_var_ifs = array_keys($if_context->getRedefinedVars($context->vars_in_scope));
        $redef_var_else = array_keys($t_else_context->getRedefinedVars($context->vars_in_scope));
        $redef_all = array_intersect($redef_var_ifs, $redef_var_else);

        $if_narrowed_var_ids = self::getOnlyNarrowedVarIds(
            $context,
            $if_context,
            $if_start_vars,
            $if_start_assigned_var_ids,
            $if_condition_assigned_var_ids,
            $redef_var_ifs,
        );
        $else_narrowed_var_ids = self::getOnlyNarrowedVarIds(
            $context,
            $t_else_context,
            $else_start_vars,
            $else_start_assigned_var_ids,
            $else_condition_assigned_var_ids,
            $redef_var_else,
        );

        //these vars were changed in both branches
        foreach ($redef_all as $redef_var_id) {
            if (self::keepsOuterMixedType(
                $statements_analyzer,
                $context,
                $if_context,
                $redef_var_id,
                $stmt->if,
                $if_condition_assigned_var_ids,
            ) && self::keepsOuterMixedType(
                $statements_analyzer,
                $context,
                $t_else_context,
                $redef_var_id,
                $stmt->else,
                $else_condition_assigned_var_ids,
            ) || isset($if_narrowed_var_ids[$redef_var_id], $else_narrowed_var_ids[$redef_var_id])) {
                $context->vars_in_scope[$redef_var_id] = $context->vars_in_scope[$redef_var_id]
                    ->addParentNodes($if_context->vars_in_scope[$redef_var_id]->parent_nodes)
                    ->addParentNodes($t_else_context->vars_in_scope[$redef_var_id]->parent_nodes);

                continue;
            }

            $context->vars_in_scope[$redef_var_id] = Type::combineUnionTypes(
                $if_context->vars_in_scope[$redef_var_id],
                $t_else_context->vars_in_scope[$redef_var_id],
            );
        }

        //these vars were changed in the if and existed before
        foreach ($redef_var_ifs as $redef_var_ifs_id) {
            if (!isset($context->vars_in_scope[$redef_var_ifs_id])) {
                continue;
            }

            if (isset($if_narrowed_var_ids[$redef_var_ifs_id])
                || self::keepsOuterMixedType(
                    $statements_analyzer,
                    $context,
                    $if_context,
                    $redef_var_ifs_id,
                    $stmt->if,
                    $if_condition_assigned_var_ids,
                )
            ) {
                $context->vars_in_scope[$redef_var_ifs_id] = $context->vars_in_scope[$redef_var_ifs_id]
                    ->addParentNodes($if_context->vars_in_scope[$redef_var_ifs_id]->parent_nodes);

                continue;
            }

            $context->vars_in_scope[$redef_var_ifs_id] = Type::combineUnionTypes(
                $context->vars_in_scope[$redef_var_ifs_id],
                $if_context->vars_in_scope[$redef_var_ifs_id],
            );
        }

        //these vars were changed in the else and existed before
        foreach ($redef_var_else as $redef_var_else_id) {
            if (!isset($context->vars_in_scope[$redef_var_else_id])) {
                continue;
            }

            if (isset($else_narrowed_var_ids[$redef_var_else_id])
                || self::keepsOuterMixedType(
                    $statements_analyzer,
                    $context,
                    $t_else_context,
                    $redef_var_else_id,
                    $stmt->else,
                    $else_condition_assigned_var_ids,
                )
            ) {
                $context->vars_in_scope[$redef_var_else_id] = $context->vars_in_scope[$redef_var_else_id]
                    ->addParentNodes($t_else_context->vars_in_scope[$redef_var_else_id]->parent_nodes);

                continue;
            }

            $context->vars_in_scope[$redef_var_else_id] = Type::combineUnionTypes(
                $context->vars_in_scope[$redef_var_else_id],
                $t_else_context->vars_in_scope[$redef_var_else_id],
            );
        }
    }

    /**
     * A mixed variable that a branch only narrowed, or wrote at some of its offsets, is still just mixed after the
     * ternary: merging the narrowed type back in would only produce a `mixed|Foo` union, as IfAnalyzer avoids
     *
     * @param array<string, mixed> $condition_assigned_var_ids
     */
    private static function keepsOuterMixedType(
        StatementsAnalyzer $statements_analyzer,
        Context $outer_context,
        Context $branch_context,
        string $var_id,
        ?PhpParser\Node\Expr $branch,
        array $condition_assigned_var_ids,
    ): bool {
        if (!isset($outer_context->vars_in_scope[$var_id], $branch_context->vars_in_scope[$var_id])) {
            return false;
        }

        $codebase = $statements_analyzer->getCodebase();
        $outer_type = $outer_context->vars_in_scope[$var_id];
        $branch_type = $branch_context->vars_in_scope[$var_id];

        // the branch context starts as a copy of the outer one, which can have assigned the variable earlier
        if (($branch_context->assigned_var_ids[$var_id] ?? null) === ($outer_context->assigned_var_ids[$var_id] ?? null)
            && isset($branch_context->possibly_assigned_var_ids[$var_id])
                === isset($outer_context->possibly_assigned_var_ids[$var_id])
        ) {
            return $outer_type->hasMixed() && UnionTypeComparator::isContainedBy($codebase, $branch_type, $outer_type);
        }

        return $branch !== null
            && !isset($condition_assigned_var_ids[$var_id])
            && IfAnalyzer::isNarrowedMixed($codebase, $branch_type, $outer_type)
            && !IfAnalyzer::mayAssignAsAWhole([new VirtualExpression($branch)], $var_id, $statements_analyzer);
    }

    /**
     * The variables that only the condition narrowed in this branch, and that the branch then left untouched. They
     * keep their outer type, as IfAnalyzer does: the condition doesn't change their value, and the narrowing says
     * nothing about the paths through the other branch
     *
     * @param array<string, Union> $start_vars the branch's variables once the condition is applied
     * @param array<string, int> $start_assigned_var_ids
     * @param array<string, mixed> $condition_assigned_var_ids
     * @param list<string> $redefined_var_ids
     * @return array<string, true>
     * @psalm-capabilities read-props
     */
    private static function getOnlyNarrowedVarIds(
        Context $outer_context,
        Context $branch_context,
        array $start_vars,
        array $start_assigned_var_ids,
        array $condition_assigned_var_ids,
        array $redefined_var_ids,
    ): array {
        $narrowed_var_ids = [];

        foreach ($redefined_var_ids as $var_id) {
            if (!isset($condition_assigned_var_ids[$var_id])
                && isset(
                    $start_vars[$var_id],
                    $branch_context->vars_in_scope[$var_id],
                    $outer_context->vars_in_scope[$var_id],
                )
                && ($branch_context->assigned_var_ids[$var_id] ?? null)
                    === ($start_assigned_var_ids[$var_id] ?? null)
                && $branch_context->vars_in_scope[$var_id]->equals($start_vars[$var_id])
            ) {
                $narrowed_var_ids[$var_id] = true;
            }
        }

        return $narrowed_var_ids;
    }

    private static function setResultType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Ternary $stmt,
        Context $context,
    ): bool {
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
