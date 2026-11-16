<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Block\IfElse;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Algebra;
use Psalm\Internal\Analyzer\AlgebraAnalyzer;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Internal\Scope\IfConditionalScope;
use Psalm\Internal\Scope\IfScope;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\ConflictingReferenceConstraint;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\BinaryOp\VirtualBooleanOr;
use Psalm\Node\Expr\VirtualBooleanNot;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Node\Name\VirtualFullyQualified;
use Psalm\Node\VirtualArg;
use Psalm\Type;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function array_any;
use function array_combine;
use function array_diff;
use function array_diff_key;
use function array_fill_keys;
use function array_filter;
use function array_intersect;
use function array_intersect_key;
use function array_key_exists;
use function array_key_first;
use function array_key_last;
use function array_keys;
use function array_merge;
use function array_reduce;
use function count;
use function in_array;
use function preg_match;
use function preg_quote;
use function spl_object_id;
use function str_ends_with;

/**
 * @internal
 */
final class IfAnalyzer
{
    /**
     * @param  array<string, Union> $pre_assignment_else_redefined_vars
     * @return false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\If_ $stmt,
        IfScope $if_scope,
        IfConditionalScope $if_conditional_scope,
        Context $if_context,
        Context $outer_context,
        array $pre_assignment_else_redefined_vars,
    ): ?bool {
        $cond_referenced_var_ids = $if_conditional_scope->cond_referenced_var_ids;

        $active_if_types = [];

        $reconcilable_if_types = Algebra::getTruthsFromFormula(
            $if_context->clauses,
            spl_object_id($stmt->cond),
            $cond_referenced_var_ids,
            $active_if_types,
        );

        if (array_any(
            $outer_context->clauses,
            static fn(Clause $clause): bool => (bool) $clause->possibilities,
        )) {
            $omit_keys = array_reduce(
                $outer_context->clauses,
                /**
                 * @param array<string> $carry
                 * @return array<string>
                 */
                static fn(array $carry, Clause $clause): array
                    => array_merge($carry, array_keys($clause->possibilities)),
                [],
            );

            $omit_keys = array_combine($omit_keys, $omit_keys);
            $omit_keys = array_diff_key($omit_keys, Algebra::getTruthsFromFormula($outer_context->clauses));

            $cond_referenced_var_ids = array_diff_key(
                $cond_referenced_var_ids,
                $omit_keys,
            );
        }

        // if the if has an || in the conditional, we cannot easily reason about it
        if ($reconcilable_if_types) {
            $changed_var_ids = [];

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
                $outer_context->check_variables
                    ? new CodeLocation(
                        $statements_analyzer->getSource(),
                        $stmt->cond instanceof PhpParser\Node\Expr\BooleanNot
                            ? $stmt->cond->expr
                            : $stmt->cond,
                        $outer_context->include_location,
                    ) : null,
            );

            foreach ($reconcilable_if_types as $var_id => $_) {
                $if_context->vars_possibly_in_scope[$var_id] = true;
            }

            if ($changed_var_ids) {
                $if_context->clauses = AlgebraAnalyzer::removeReconciledClauses(
                    $if_context->clauses,
                    $changed_var_ids,
                    $if_context->vars_in_scope,
                    $statements_analyzer,
                )[0];

                foreach ($changed_var_ids as $changed_var_id => $_) {
                    foreach ($if_context->vars_in_scope as $var_id => $_) {
                        if (preg_match('/' . preg_quote($changed_var_id, '/') . '[\]\[\-]/', $var_id)
                            && !array_key_exists($var_id, $changed_var_ids)
                            && !array_key_exists($var_id, $cond_referenced_var_ids)
                        ) {
                            $if_context->removePossibleReference($var_id);
                        }
                    }
                }
            }

            $if_scope->if_cond_changed_var_ids = $changed_var_ids;
        }

        $if_context->reconciled_expression_clauses = [];

        $outer_context->vars_possibly_in_scope = [
            ...$if_context->vars_possibly_in_scope,
            ...$outer_context->vars_possibly_in_scope,
        ];

        $old_if_context = clone $if_context;

        $codebase = $statements_analyzer->getCodebase();

        $assigned_var_ids = $if_context->assigned_var_ids;
        $possibly_assigned_var_ids = $if_context->possibly_assigned_var_ids;
        $if_context->assigned_var_ids = [];
        $if_context->fully_assigned_var_ids = [];
        $if_context->possibly_assigned_var_ids = [];

        if ($statements_analyzer->analyze(
            $stmt->stmts,
            $if_context,
        ) === false
        ) {
            return false;
        }

        $outer_context->removeClausesOfVarsChangedIn($if_context, $statements_analyzer);

        $final_actions = ScopeAnalyzer::getControlActions(
            $stmt->stmts,
            $statements_analyzer->node_data,
            [],
        );
        // has a return/throw at end
        $has_ending_statements = $final_actions === [ScopeAnalyzer::ACTION_END];

        $has_leaving_statements = $has_ending_statements
            || (count($final_actions) && !in_array(ScopeAnalyzer::ACTION_NONE, $final_actions, true));

        $has_break_statement = $final_actions === [ScopeAnalyzer::ACTION_BREAK];
        $has_continue_statement = $final_actions === [ScopeAnalyzer::ACTION_CONTINUE];

        $if_scope->if_actions = $final_actions;
        $if_scope->final_actions = $final_actions;

        /** @var array<string, int> */
        $new_assigned_var_ids = $if_context->assigned_var_ids;
        /** @var array<string, bool> */
        $new_possibly_assigned_var_ids = $if_context->possibly_assigned_var_ids;

        $if_context->assigned_var_ids = array_merge($assigned_var_ids, $new_assigned_var_ids);
        $if_context->possibly_assigned_var_ids = array_merge(
            $possibly_assigned_var_ids,
            $new_possibly_assigned_var_ids,
        );

        $outer_context->references_rebound_in_blocks
            += $outer_context->mergeConstraintsOfReferencesReboundIn($if_context, $stmt->stmts, $codebase);

        foreach ($if_context->byref_constraints as $var_id => $byref_constraint) {
            if (isset($outer_context->references_rebound_in_blocks[$var_id])) {
                continue;
            }

            if (isset($outer_context->byref_constraints[$var_id])
                && $byref_constraint->type
                && ($outer_constraint_type = $outer_context->byref_constraints[$var_id]->type)
                && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $byref_constraint->type,
                    $outer_constraint_type,
                )
            ) {
                IssueBuffer::maybeAdd(
                    new ConflictingReferenceConstraint(
                        'There is more than one pass-by-reference constraint on ' . $var_id
                            . ' between ' . $byref_constraint->type->getId()
                            . ' and ' . $outer_constraint_type->getId(),
                        new CodeLocation($statements_analyzer, $stmt, $outer_context->include_location, true),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            } else {
                $outer_context->byref_constraints[$var_id] = $byref_constraint;
            }
        }

        if (!$has_leaving_statements) {
            self::updateIfScope(
                $statements_analyzer,
                $if_scope,
                $if_context,
                $outer_context,
                array_merge($new_assigned_var_ids, $if_conditional_scope->assigned_in_conditional_var_ids),
                $new_possibly_assigned_var_ids,
                $if_scope->if_cond_changed_var_ids,
                assigned_in_conditional_var_ids: $if_conditional_scope->assigned_in_conditional_var_ids,
                branch_nodes: $stmt->stmts,
                start_remove_vars: $old_if_context->parent_remove_vars,
            );

            if ($if_scope->reasonable_clauses) {
                // remove all reasonable clauses that would be negated by the if stmts
                $if_scope->reasonable_clauses = Context::filterClausesOfAssignedVars(
                    $if_scope->reasonable_clauses,
                    $new_assigned_var_ids,
                    $if_context,
                    $statements_analyzer,
                );
            }
        } else {
            if (!$has_break_statement) {
                $if_scope->reasonable_clauses = [];
            }

            // unlike its assignments, the constants a `defined()` of the condition recorded exist after it
            if ($if_scope->post_leaving_if_context) {
                foreach ($outer_context->constants as $const_name => $const_type) {
                    if (!isset($if_scope->post_leaving_if_context->constants[$const_name])) {
                        $if_scope->post_leaving_if_context->constants[$const_name] = $const_type;
                        $if_scope->post_leaving_if_context->vars_in_scope[$const_name] = $const_type;
                    }
                }
            }

            // If we're assigning inside
            if ($if_conditional_scope->assigned_in_conditional_var_ids
                && $if_scope->post_leaving_if_context
            ) {
                self::addConditionallyAssignedVarsToContext(
                    $statements_analyzer,
                    $stmt->cond,
                    $if_scope->post_leaving_if_context,
                    $outer_context,
                    $if_conditional_scope->assigned_in_conditional_var_ids,
                );
            }
        }

        // update the parent context as necessary, but only if we can safely reason about type negation.
        // We only update vars that changed both at the start of the if block and then again by an assignment
        // in the if statement.
        if ($if_scope->negated_types) {
            $vars_to_update = array_intersect(
                array_keys($pre_assignment_else_redefined_vars),
                array_keys($if_scope->negated_types),
            );

            // update() replaces the part of the type that enters the if, which is only known when the code skipping
            // the if cannot see the same values (unlike with `if ($b || $a === null)`). Without an else, the type
            // after the if is then the one at the end of its body or at the start of the implicit else. A mixed
            // type, which update() cannot split either, and a type the body leaves as it is are left to it.
            $overlapping_var_ids = $stmt->else || $stmt->elseifs ? [] : array_filter(
                $vars_to_update,
                static function (string $var_id) use (
                    $old_if_context,
                    $if_context,
                    $pre_assignment_else_redefined_vars,
                    $codebase,
                ): bool {
                    $if_type = $old_if_context->vars_in_scope[$var_id] ?? null;
                    $else_type = $pre_assignment_else_redefined_vars[$var_id];

                    return $if_type !== null
                        && !($if_context->vars_in_scope[$var_id] ?? null)?->equals($if_type)
                        && !$if_type->hasMixed()
                        && !$else_type->hasMixed()
                        && !$else_type->isNever()
                        && !$else_type->failed_reconciliation
                        && UnionTypeComparator::canExpressionTypesBeIdentical($codebase, $if_type, $else_type);
                },
            );

            $outer_context->update(
                $old_if_context,
                $if_context,
                $has_leaving_statements,
                array_diff($vars_to_update, $overlapping_var_ids),
                $if_scope->updated_vars,
            );

            // after a leaving if, the code continues from the else, whose type IfElseAnalyzer applies
            if (!$has_leaving_statements) {
                foreach ($overlapping_var_ids as $var_id) {
                    if ($if_context->hasVariable($var_id)) {
                        $outer_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $if_context->vars_in_scope[$var_id],
                            $pre_assignment_else_redefined_vars[$var_id],
                            $codebase,
                        );
                        $if_scope->updated_vars[$var_id] = true;
                    }
                }
            }
        }

        if (!$has_ending_statements) {
            $vars_possibly_in_scope = array_diff_key(
                $if_context->vars_possibly_in_scope,
                $outer_context->vars_possibly_in_scope,
            );

            if ($if_context->loop_scope) {
                if (!$has_continue_statement && !$has_break_statement) {
                    $if_scope->new_vars_possibly_in_scope = $vars_possibly_in_scope;
                }

                $if_context->loop_scope->vars_possibly_in_scope = [
                    ...$vars_possibly_in_scope,
                    ...$if_context->loop_scope->vars_possibly_in_scope,
                ];
            } elseif (!$has_leaving_statements) {
                $if_scope->new_vars_possibly_in_scope = $vars_possibly_in_scope;
            }
        }

        if ($outer_context->collect_exceptions) {
            $outer_context->mergeExceptions($if_context);
        }

        // Track references set in the if to make sure they aren't reused later
        $outer_context->updateReferencesPossiblyFromConfusingScope(
            $if_context,
            $statements_analyzer,
        );

        return null;
    }

    /**
     * @param array<string, int> $assigned_in_conditional_var_ids
     */
    public static function addConditionallyAssignedVarsToContext(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $cond,
        Context $post_leaving_if_context,
        Context $post_if_context,
        array $assigned_in_conditional_var_ids,
    ): void {
        // this filters out coercions to expected types in ArgumentAnalyzer
        $assigned_in_conditional_var_ids = array_filter($assigned_in_conditional_var_ids);

        if (!$assigned_in_conditional_var_ids) {
            return;
        }

        $exprs = self::getDefinitelyEvaluatedOredExpressions($cond);

        // if there was no assignment in the first expression it's safe to proceed
        $old_node_data = $statements_analyzer->node_data;
        $statements_analyzer->node_data = clone $old_node_data;

        IssueBuffer::startRecording();

        foreach ($exprs as $expr) {
            if ($expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
                $fake_not = new VirtualBooleanOr(
                    self::negateExpr($expr->left),
                    self::negateExpr($expr->right),
                    $expr->getAttributes(),
                );
            } else {
                $fake_not = self::negateExpr($expr);
            }

            $fake_negated_expr = new VirtualFuncCall(
                new VirtualFullyQualified('assert'),
                [new VirtualArg(
                    $fake_not,
                    false,
                    false,
                    $expr->getAttributes(),
                )],
                $expr->getAttributes(),
            );

            $post_leaving_if_context->inside_negation = !$post_leaving_if_context->inside_negation;

            ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $fake_negated_expr,
                $post_leaving_if_context,
            );

            $post_leaving_if_context->inside_negation = !$post_leaving_if_context->inside_negation;
        }

        IssueBuffer::clearRecordingLevel();
        IssueBuffer::stopRecording();

        $statements_analyzer->node_data = $old_node_data;

        foreach ($assigned_in_conditional_var_ids as $var_id => $_) {
            if (isset($post_leaving_if_context->vars_in_scope[$var_id])) {
                $post_if_context->vars_in_scope[$var_id] = $post_leaving_if_context->vars_in_scope[$var_id];
            }
        }
    }

    /**
     * Returns all expressions inside an ored expression
     *
     * @return non-empty-list<PhpParser\Node\Expr>
     * @psalm-mutation-free
     */
    private static function getDefinitelyEvaluatedOredExpressions(PhpParser\Node\Expr $stmt): array
    {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalXor
        ) {
            return [
                ...self::getDefinitelyEvaluatedOredExpressions($stmt->left),
                ...self::getDefinitelyEvaluatedOredExpressions($stmt->right),
            ];
        }

        return [$stmt];
    }

    private static function negateExpr(PhpParser\Node\Expr $expr): PhpParser\Node\Expr
    {
        if ($expr instanceof PhpParser\Node\Expr\BooleanNot) {
            return $expr->expr;
        }

        return new VirtualBooleanNot($expr, $expr->getAttributes());
    }

    /**
     * @param  array<string, int>    $assigned_var_ids
     * @param  array<string, bool>   $possibly_assigned_var_ids
     * @param  array<string, bool>   $newly_reconciled_var_ids
     * @param  array<string, int>    $assigned_in_conditional_var_ids
     * @param  array<PhpParser\Node> $branch_nodes the code of the branch: what it rebinds by reference is detached
     *                                             in the outer context, unless $detach_references is false
     * @param  array<string, int>    $start_remove_vars the stamps the branch started with
     */
    public static function updateIfScope(
        StatementsAnalyzer $statements_analyzer,
        IfScope $if_scope,
        Context $if_context,
        Context $outer_context,
        array $assigned_var_ids,
        array $possibly_assigned_var_ids,
        array $newly_reconciled_var_ids,
        bool $update_new_vars = true,
        array $assigned_in_conditional_var_ids = [],
        array $branch_nodes = [],
        array $start_remove_vars = [],
        bool $detach_references = true,
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        $if_scope->addBranchReachingTheEnd($if_context);
        $if_scope->removed_var_ids += array_fill_keys(
            $outer_context->getVarsRemovedIn($if_context, $start_remove_vars),
            true,
        );

        if ($branch_nodes && $detach_references) {
            $outer_context->detachReferencesReboundIn($if_context, $statements_analyzer, $branch_nodes);
        }

        $redefined_vars = $if_context->getRedefinedVars($outer_context->vars_in_scope);

        foreach (array_diff_key($if_context->vars_in_scope, $outer_context->vars_in_scope) as $new_var => $type) {
            // only variables the branch defines: `$a->b` and `$a['b']` are narrowings of an expression, `isset($a)`
            // doesn't define `$a`, and the type of a variable possibly defined before the branch (by an earlier
            // iteration) or by its condition (which the paths skipping the branch evaluate too) isn't all in it
            if (!preg_match('/^\$[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $new_var)
                || (!isset($assigned_var_ids[$new_var]) && !isset($possibly_assigned_var_ids[$new_var]))
                || isset($outer_context->vars_possibly_in_scope[$new_var])
                || isset($assigned_in_conditional_var_ids[$new_var])
            ) {
                continue;
            }

            $if_scope->partial_new_vars[$new_var] = isset($if_scope->partial_new_vars[$new_var])
                ? Type::combineUnionTypes($if_scope->partial_new_vars[$new_var], $type, $codebase)
                : $type;
        }

        $branch_range = $branch_nodes
            ? [
                $branch_nodes[array_key_first($branch_nodes)]->getStartFilePos(),
                $branch_nodes[array_key_last($branch_nodes)]->getEndFilePos(),
            ]
            : null;

        foreach ($if_context->vars_possibly_in_scope as $var_id => $_) {
            if (!isset($if_context->vars_in_scope[$var_id])
                && !isset($outer_context->vars_possibly_in_scope[$var_id])
            ) {
                // the assignments of the branch, not those another branch overwrote
                $if_scope->untyped_new_vars[$var_id] = [
                    ...$if_scope->untyped_new_vars[$var_id] ?? [],
                    ...$branch_range
                        ? $statements_analyzer->getParentNodesForPossiblyUndefinedVariable($var_id, $branch_range)
                        : [],
                ];
            }
        }

        if ($if_scope->new_vars === null) {
            if ($update_new_vars) {
                $if_scope->new_vars = array_diff_key($if_context->vars_in_scope, $outer_context->vars_in_scope);
            }
        } else {
            foreach ($if_scope->new_vars as $new_var => $type) {
                if (!$if_context->hasVariable($new_var)) {
                    unset($if_scope->new_vars[$new_var]);
                } elseif (str_ends_with($new_var, ']')
                    && $type->possibly_undefined !== $if_context->vars_in_scope[$new_var]->possibly_undefined
                ) {
                    unset($if_scope->new_vars[$new_var]);
                    $if_scope->unknown_offsets[$new_var] = true;
                } else {
                    $if_scope->new_vars[$new_var] = Type::combineUnionTypes(
                        $type,
                        $if_context->vars_in_scope[$new_var],
                        $codebase,
                    );
                }
            }
        }

        $possibly_redefined_vars = $redefined_vars;

        foreach ($possibly_redefined_vars as $var_id => $type) {
            if (!isset($possibly_assigned_var_ids[$var_id])
                && isset($newly_reconciled_var_ids[$var_id])
            ) {
                unset($possibly_redefined_vars[$var_id]);
            } elseif (isset($outer_context->vars_in_scope[$var_id])
                && $outer_context->vars_in_scope[$var_id]->hasMixed()
                && !isset($assigned_var_ids[$var_id])
                && !isset($possibly_assigned_var_ids[$var_id])
                && UnionTypeComparator::isContainedBy($codebase, $type, $outer_context->vars_in_scope[$var_id])
            ) {
                // a mixed var that was only narrowed (never written) in this branch is still just mixed,
                // merging the narrowed type back in would only produce a `mixed|Foo` union
                $possibly_redefined_vars[$var_id] = $outer_context->vars_in_scope[$var_id]
                    ->addParentNodes($type->parent_nodes);
            }
        }

        if ($if_scope->assigned_var_ids === null) {
            $if_scope->assigned_var_ids = $assigned_var_ids;
        } else {
            $if_scope->assigned_var_ids = array_intersect_key($assigned_var_ids, $if_scope->assigned_var_ids);
        }

        $if_scope->possibly_assigned_var_ids += $possibly_assigned_var_ids;
        $if_scope->reassigned_var_ids += $if_context->fully_assigned_var_ids;

        if ($if_scope->redefined_vars === null) {
            $if_scope->redefined_vars = $redefined_vars;
            $if_scope->possibly_redefined_vars = $possibly_redefined_vars;
        } else {
            foreach ($if_scope->redefined_vars as $redefined_var => $type) {
                if (!isset($redefined_vars[$redefined_var])) {
                    unset($if_scope->redefined_vars[$redefined_var]);
                } else {
                    $if_scope->redefined_vars[$redefined_var] = Type::combineUnionTypes(
                        $redefined_vars[$redefined_var],
                        $type,
                        $codebase,
                    );

                    if (isset($outer_context->vars_in_scope[$redefined_var])
                        && $if_scope->redefined_vars[$redefined_var]->equals(
                            $outer_context->vars_in_scope[$redefined_var],
                        )
                    ) {
                        unset($if_scope->redefined_vars[$redefined_var]);
                    }
                }
            }

            foreach ($possibly_redefined_vars as $var => $type) {
                $if_scope->possibly_redefined_vars[$var] = Type::combineUnionTypes(
                    $type,
                    $if_scope->possibly_redefined_vars[$var] ?? null,
                    $codebase,
                );
            }
        }
    }
}
