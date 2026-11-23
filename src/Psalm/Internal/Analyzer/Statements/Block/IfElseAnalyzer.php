<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Exception\ScopeAnalysisException;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfElse\ElseAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfElse\ElseIfAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfElse\IfAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\GuardedClasses;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\Scope\IfConditionalScope;
use Psalm\Internal\Scope\IfScope;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\IssueBuffer;
use Psalm\Storage\Assertion\ArrayKeyExists;
use Psalm\Type;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function array_fill_keys;
use function array_filter;
use function array_intersect_key;
use function array_keys;
use function array_merge;
use function array_slice;
use function array_unique;
use function count;
use function in_array;
use function preg_match;
use function preg_quote;
use function str_contains;
use function str_starts_with;
use function strpbrk;
use function substr;

/**
 * @internal
 */
final class IfElseAnalyzer
{
    /**
     * System of type substitution and deletion
     *
     * for example
     *
     * x: A|null
     *
     * if (x)
     *   (x: A)
     *   x = B  -- effects: remove A from the type of x, add B
     * else
     *   (x: null)
     *   x = C  -- effects: remove null from the type of x, add C
     *
     *
     * x: A|null
     *
     * if (!x)
     *   (x: null)
     *   throw new Exception -- effects: remove null from the type of x
     *
     * @return null|false
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\If_ $stmt,
        Context $context,
    ): ?bool {
        $codebase = $statements_analyzer->getCodebase();

        $if_scope = new IfScope();

        $parent_remove_vars_before_if = $context->parent_remove_vars;
        $pre_if_vars = $context->vars_in_scope;

        // We need to clone the original context for later use if we're exiting in this if conditional
        if ($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp
            || ($stmt->cond instanceof PhpParser\Node\Expr\BooleanNot
                && $stmt->cond->expr instanceof PhpParser\Node\Expr\BinaryOp)
        ) {
            $final_actions = ScopeAnalyzer::getControlActions(
                $stmt->stmts,
                null,
                [],
            );

            $has_leaving_statements = $final_actions === [ScopeAnalyzer::ACTION_END]
                || (count($final_actions) && !in_array(ScopeAnalyzer::ACTION_NONE, $final_actions, true));

            if ($has_leaving_statements) {
                $if_scope->post_leaving_if_context = clone $context;
            }
        }

        $branch_point = $context->branch_point ?: (int) $stmt->getAttribute('startFilePos');

        $pre_cond_reconciled_clauses = $context->reconciled_expression_clauses;
        $pre_cond_clauses = $context->clauses;
        $pre_cond_remove_vars = $context->parent_remove_vars;

        // the else of an `||` is analysed again from where the condition started, not from where it ended
        $pre_condition_context = $stmt->cond instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
            || $stmt->cond instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
                ? clone $context
                : null;

        try {
            $if_conditional_scope = self::analyzeCondition(
                $statements_analyzer,
                $stmt,
                $context,
                $codebase,
                $if_scope,
                $branch_point,
            );

            // this is the context for stuff that happens within the `if` block
            $if_context = $if_conditional_scope->if_context;

            // this is the context for stuff that happens after the `if` block
            $post_if_context = $if_conditional_scope->post_if_context;
            $assigned_in_conditional_var_ids = $if_conditional_scope->assigned_in_conditional_var_ids;
        } catch (ScopeAnalysisException) {
            return false;
        }

        $reassigned_var_ids = FormulaGenerator::getVarsAssignedByOredRightSides(
            $stmt->cond,
            $context->self,
            $statements_analyzer->node_data,
        );

        if ($if_scope->post_leaving_if_context) {
            self::applyCalleeEffectsOfCondition(
                $statements_analyzer,
                $if_scope->post_leaving_if_context,
                $post_if_context,
                $assigned_in_conditional_var_ids,
            );
        }

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

        $if_scope->reasonable_clauses = $if_context->clauses;

        $temp_else_context = clone $post_if_context;

        $changed_var_ids = [];

        if ($if_scope->negated_types) {
            [$temp_else_context->vars_in_scope, $temp_else_context->references_in_scope] =
                Reconciler::reconcileKeyedTypes(
                    $if_scope->negated_types,
                    [],
                    $temp_else_context->vars_in_scope,
                    $temp_else_context->references_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    $statements_analyzer->getTemplateTypeMap() ?: [],
                    $context->inside_loop,
                    $context->check_variables
                        ? new CodeLocation(
                            $statements_analyzer->getSource(),
                            $stmt->cond instanceof PhpParser\Node\Expr\BooleanNot
                                ? $stmt->cond->expr
                                : $stmt->cond,
                            $context->include_location,
                        ) : null,
                );
        }

        // we calculate the vars redefined in a hypothetical else statement to determine
        // which vars of the if we can safely change
        $pre_assignment_else_redefined_vars = array_intersect_key(
            $temp_else_context->getRedefinedVars($context->vars_in_scope, true),
            $changed_var_ids,
        );

        $references_rebound_before = $context->references_rebound_in_blocks;

        // check the if
        if (IfAnalyzer::analyze(
            $statements_analyzer,
            $stmt,
            $if_scope,
            $if_conditional_scope,
            $if_context,
            $context,
            $pre_assignment_else_redefined_vars,
        ) === false) {
            return false;
        }

        // this has to go on a separate line because the phar compactor messes with precedence
        $scope_to_clone = $if_scope->post_leaving_if_context ?? $post_if_context;
        $else_context = clone $scope_to_clone;

        // the else runs when every operand of an `||` was evaluated and falsy, so what they assigned is known there
        $else_rederived_var_ids = array_fill_keys(array_keys($reassigned_var_ids), 1);

        if ($stmt->else || $stmt->elseifs) {
            $else_rederived_var_ids += array_filter($assigned_in_conditional_var_ids);
        }

        if (!$if_scope->post_leaving_if_context
            && $else_rederived_var_ids !== []
            && $pre_condition_context !== null
        ) {
            IfAnalyzer::addConditionallyAssignedVarsToContext(
                $statements_analyzer,
                $stmt->cond,
                $pre_condition_context,
                $else_context,
                $else_rederived_var_ids,
            );
        }
        $else_context->clauses = Algebra::simplifyCNF(
            [...$else_context->clauses, ...$if_scope->negated_clauses],
        );

        // check the elseifs
        foreach ($stmt->elseifs as $elseif) {
            if (ElseIfAnalyzer::analyze(
                $statements_analyzer,
                $elseif,
                $if_scope,
                $else_context,
                $context,
                $codebase,
                $else_context->branch_point ?: (int) $stmt->getAttribute('startFilePos'),
            ) === false) {
                return false;
            }
        }

        if ($stmt->else) {
            if ($codebase->alter_code && $else_context->branch_point === null) {
                $else_context->branch_point = (int) $stmt->getAttribute('startFilePos');
            }
        }

        if (ElseAnalyzer::analyze(
            $statements_analyzer,
            $stmt->else,
            $if_scope,
            $else_context,
            $context,
            $stmt->cond,
        ) === false) {
            return false;
        }

        $context->references_rebound_in_blocks = $references_rebound_before;

        $statements_analyzer->node_data->setImplicitElseImpossible($stmt, $if_scope->implicit_else_is_impossible);

        if (count($if_scope->if_actions) && !in_array(ScopeAnalyzer::ACTION_NONE, $if_scope->if_actions, true)
            && !$stmt->elseifs
        ) {
            $context->clauses = $else_context->clauses;
            foreach ($else_context->vars_in_scope as $var_id => $type) {
                $context->vars_in_scope[$var_id] = $type;
            }

            // the negated clauses leave out what a right side of an `||` reassigns: the else derived its type instead
            self::removeMixedAssignmentsOfNarrowedVars(
                $statements_analyzer,
                $post_if_context,
                [
                    ...$pre_assignment_else_redefined_vars,
                    ...array_intersect_key($else_context->vars_in_scope, $reassigned_var_ids),
                ],
            );
        } elseif ($stmt->elseifs
            && !$stmt->else
            && self::everyBranchLeaves($if_scope)
        ) {
            // what follows only runs once every condition is false
            self::removeMixedAssignmentsOfNarrowedVars(
                $statements_analyzer,
                $post_if_context,
                $else_context->getRedefinedVars($context->vars_in_scope, true),
            );
        }

        if ($context->loop_scope) {
            $context->loop_scope->final_actions = array_unique(
                array_merge(
                    $context->loop_scope->final_actions,
                    $if_scope->final_actions,
                ),
            );
        }

        $context->vars_possibly_in_scope = [
            ...$context->vars_possibly_in_scope,
            ...$if_scope->new_vars_possibly_in_scope,
        ];

        $context->possibly_assigned_var_ids = [
            ...$context->possibly_assigned_var_ids,
            ...$if_scope->possibly_assigned_var_ids ?: [],
        ];

        // every way to the end of the if goes through a branch, the implicit else included
        if ($if_scope->null_array_offsets_read !== null) {
            $context->null_array_offsets_read = $if_scope->null_array_offsets_read;
        }

        // vars can only be defined/redefined if there was an else (defined in every block)
        $context->assigned_var_ids = array_merge(
            $context->assigned_var_ids,
            $if_scope->assigned_var_ids ?: [],
        );

        foreach ($if_scope->unknown_offsets as $var_id => $_) {
            unset($context->vars_in_scope[$var_id], $context->vars_possibly_in_scope[$var_id]);
        }

        if ($if_scope->new_vars) {
            foreach ($if_scope->new_vars as $var_id => &$type) {
                // a variable every branch assigns no longer holds what it held before: only the use of variables
                // is tracked through the assignments made before then (a branch defining it by narrowing, like
                // the implicit else of `if (empty($a)) { $a = 1; }`, keeps it, and its taints)
                if (isset($context->vars_possibly_in_scope[$var_id])
                    && $statements_analyzer->data_flow_graph
                    && ($statements_analyzer->data_flow_graph instanceof VariableUseGraph
                        || !isset($if_scope->assigned_var_ids[$var_id]))
                ) {
                    $type = $type->addParentNodes(
                        $statements_analyzer->getParentNodesForPossiblyUndefinedVariable($var_id),
                    );
                }

                $context->vars_in_scope[$var_id] = $type;
            }
            unset($type);
        }

        foreach ($if_scope->partial_new_vars as $var_id => $type) {
            if (isset($context->vars_in_scope[$var_id])) {
                continue;
            }

            if (isset($if_scope->untyped_new_vars[$var_id])) {
                // what the branches that don't type it assigned flows to the uses too
                $type = Type::getMixed()->setParentNodes([
                    ...$type->parent_nodes,
                    ...$if_scope->untyped_new_vars[$var_id],
                ]);
            }

            $context->vars_in_scope[$var_id] = $type->setPossiblyUndefined(true);
        }

        $vars_before_redefinitions = $context->vars_in_scope;

        if ($if_scope->redefined_vars) {
            foreach ($if_scope->redefined_vars as $var_id => $type) {
                $previous_type = $context->vars_in_scope[$var_id] ?? null;
                $context->vars_in_scope[$var_id] = $type;
                $if_scope->updated_vars[$var_id] = true;

                // what was known of an offset or property of a variable a branch assigned is no longer true
                if ($previous_type && isset($if_scope->reassigned_var_ids[$var_id])) {
                    $context->removeDescendents($var_id, $previous_type, $type, $statements_analyzer);
                }

                if ($if_scope->reasonable_clauses) {
                    $if_scope->reasonable_clauses = Context::filterClauses(
                        $var_id,
                        $if_scope->reasonable_clauses,
                        $context->vars_in_scope[$var_id] ?? null,
                        $statements_analyzer,
                    );
                }
            }
        }

        if ($if_scope->reasonable_clauses
            && (count($if_scope->reasonable_clauses) > 1 || !$if_scope->reasonable_clauses[0]->wedge)
        ) {
            $context->clauses = Algebra::simplifyCNF(
                [...$if_scope->reasonable_clauses, ...$context->clauses],
            );
        }

        if ($if_scope->possibly_redefined_vars) {
            foreach ($if_scope->possibly_redefined_vars as $var_id => $type) {
                if (isset($context->vars_in_scope[$var_id])) {
                    if (!$type->failed_reconciliation
                        && !isset($if_scope->updated_vars[$var_id])
                    ) {
                        $combined_type = Type::combineUnionTypes(
                            $context->vars_in_scope[$var_id],
                            $type,
                            $codebase,
                        );

                        if (!$combined_type->equals($context->vars_in_scope[$var_id], true, false)) {
                            $context->removeDescendents($var_id, $combined_type, $type, $statements_analyzer);
                        }

                        $context->vars_in_scope[$var_id] = $combined_type;
                    } else {
                        $updated_type = $context->vars_in_scope[$var_id]->addParentNodes($type->parent_nodes);

                        // what a later branch writes (`elseif`, `else`) is not in the type the first branch's
                        // update left
                        if (!$type->failed_reconciliation
                            && !$type->hasMixed()
                            && isset($if_scope->possibly_assigned_var_ids[$var_id])
                            && !UnionTypeComparator::isContainedBy($codebase, $type, $updated_type)
                        ) {
                            $updated_type = Type::combineUnionTypes($updated_type, $type, $codebase);
                            $context->removeDescendents($var_id, $updated_type, $type, $statements_analyzer);
                        }

                        $context->vars_in_scope[$var_id] = $updated_type;
                    }
                }
            }
        }

        // an offset or a property is known after the if as the branches reaching its end leave it: not at all once
        // one of them forgot it or the type Context::update() left, and as they all leave it when the redefinition of
        // a variable a branch wrote to dropped it (a variable only narrowed is narrowed again, which forgets it)
        foreach ($vars_before_redefinitions + $context->vars_in_scope as $var_id => $_) {
            if (!str_contains($var_id, '[') && !str_contains($var_id, '->')) {
                continue;
            }

            $end_types = [];

            foreach ($if_scope->end_contexts as $end_context) {
                if (!isset($end_context->vars_in_scope[$var_id])) {
                    if (self::isForgottenIn($end_context, $var_id, $parent_remove_vars_before_if)
                        || (isset($if_scope->updated_vars[$var_id]) && isset($if_context->vars_in_scope[$var_id]))
                    ) {
                        // what was bound to it by reference stays bound together
                        $context->removePossibleReference($var_id);
                    }

                    continue 2;
                }

                $end_types[] = $end_context->vars_in_scope[$var_id];
            }

            if ($end_types
                && !isset($context->vars_in_scope[$var_id])
                && self::isUnderAssignedVar($var_id, $if_scope->possibly_assigned_var_ids)
            ) {
                $context->vars_in_scope[$var_id] = Type::combineUnionTypeArray($end_types, $codebase);
            }
        }

        if (!$stmt->else && !$stmt->elseifs) {
            self::addConditionallyDefinedVarClauses($context, $if_context, $if_scope, $pre_if_vars);
        }

        if (!in_array(ScopeAnalyzer::ACTION_NONE, $if_scope->final_actions, true)) {
            $context->has_returned = true;
        }

        return null;
    }

    /**
     * @param array<string, bool> $assigned_var_ids
     * @psalm-pure
     */
    private static function isUnderAssignedVar(string $var_id, array $assigned_var_ids): bool
    {
        foreach ($assigned_var_ids as $assigned_var_id => $_) {
            if (str_starts_with($var_id, $assigned_var_id . '[') || str_starts_with($var_id, $assigned_var_id . '->')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a branch that no longer has the variable forgot it since the stamps $parent_remove_vars were taken: it
     * changed something the variable depends on (its variable, a key), or forgot it after a call. A variable it unset
     * is not forgotten, nor one it only narrowed the variable of
     *
     * @param array<string, int> $parent_remove_vars
     * @psalm-mutation-free
     */
    private static function isForgottenIn(Context $branch_context, string $var_id, array $parent_remove_vars): bool
    {
        foreach ($branch_context->parent_remove_vars as $changed_var_id => $stamp) {
            if ($stamp !== ($parent_remove_vars[$changed_var_id] ?? null)
                && ($changed_var_id === $var_id
                    ? isset($branch_context->vars_possibly_in_scope[$var_id])
                    : preg_match('/' . preg_quote($changed_var_id, '/') . '[\]\[\-]/', $var_id) === 1)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * A variable only the if assigns is defined whenever its condition held: `!cond || defined($x)`, added as one
     * clause per clause of the negated condition, so that the same condition checked again defines it.
     *
     * @param array<string, Union> $pre_if_vars
     */
    private static function addConditionallyDefinedVarClauses(
        Context $context,
        Context $if_context,
        IfScope $if_scope,
        array $pre_if_vars,
    ): void {
        if (!$if_scope->negated_clauses) {
            return;
        }

        foreach ($if_scope->negated_clauses as $negated_clause) {
            if ($negated_clause->wedge || !$negated_clause->reconcilable) {
                return;
            }
        }

        $defined = new ArrayKeyExists();
        $new_clauses = [];

        foreach ($context->vars_in_scope as $var_id => $type) {
            if (isset($pre_if_vars[$var_id])
                || !$type->possibly_undefined
                || $type->possibly_undefined_from_try
                || !isset($if_context->vars_in_scope[$var_id])
                || $if_context->vars_in_scope[$var_id]->possibly_undefined
                || strpbrk($var_id, '[-:') !== false
            ) {
                continue;
            }

            foreach ($if_scope->negated_clauses as $negated_clause) {
                if (isset($negated_clause->possibilities[$var_id])) {
                    continue 2;
                }
            }

            foreach ($if_scope->negated_clauses as $negated_clause) {
                $possibilities = $negated_clause->possibilities;
                $possibilities[$var_id] = [(string) $defined => $defined];

                $new_clauses[] = new Clause(
                    $possibilities,
                    $negated_clause->creating_conditional_id,
                    $negated_clause->creating_object_id,
                );
            }
        }

        if ($new_clauses) {
            $context->clauses = Algebra::simplifyCNF([...$context->clauses, ...$new_clauses]);
        }
    }

    /**
     * The context the code after a leaving if starts from is taken before its condition, so it misses what the calls of
     * the condition did: the properties and memoized calls an impure call forgets, and the properties a callee sets
     * (recorded as assigned at offset 0). The variables the condition assigns itself are derived again from it
     * (IfAnalyzer::addConditionallyAssignedVarsToContext()).
     *
     * @param array<string, int> $assigned_in_conditional_var_ids
     */
    private static function applyCalleeEffectsOfCondition(
        StatementsAnalyzer $statements_analyzer,
        Context $post_leaving_if_context,
        Context $post_if_context,
        array $assigned_in_conditional_var_ids,
    ): void {
        foreach ($post_leaving_if_context->vars_in_scope as $var_id => $_) {
            $post_cond_type = $post_if_context->vars_in_scope[$var_id] ?? null;

            if ($post_cond_type === null) {
                $post_leaving_if_context->remove($var_id, false);
                $post_leaving_if_context->vars_possibly_in_scope[$var_id] = true;
            } elseif (($assigned_in_conditional_var_ids[$var_id] ?? null) === 0
                && (str_contains($var_id, '->') || str_contains($var_id, '::'))
            ) {
                $post_leaving_if_context->vars_in_scope[$var_id] = $post_cond_type;
            } else {
                continue;
            }

            $post_leaving_if_context->clauses = Context::filterClauses(
                $var_id,
                $post_leaving_if_context->clauses,
                $post_cond_type,
                $statements_analyzer,
            );
        }
    }

    /**
     * A mixed variable that is narrowed by the conditions it is checked against is not reported where it is
     * assigned: the first use of the variable is the check.
     *
     * @param array<string, Union> $narrowed_vars
     */
    private static function removeMixedAssignmentsOfNarrowedVars(
        StatementsAnalyzer $statements_analyzer,
        Context $post_if_context,
        array $narrowed_vars,
    ): void {
        foreach ($narrowed_vars as $var_id => $reconciled_type) {
            $first_appearance = $statements_analyzer->getFirstAppearance($var_id);

            if ($first_appearance
                && isset($post_if_context->vars_in_scope[$var_id])
                && $post_if_context->vars_in_scope[$var_id]->hasMixed()
                && !$reconciled_type->hasMixed()
            ) {
                if (!$post_if_context->collect_initializations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                ) {
                    $parent_source = $statements_analyzer->getSource();

                    $functionlike_storage = $parent_source instanceof FunctionLikeAnalyzer
                        ? $parent_source->getFunctionLikeStorage($statements_analyzer)
                        : null;

                    if (!$functionlike_storage
                            || (!$parent_source->getSource() instanceof TraitAnalyzer
                                && !isset($functionlike_storage->param_lookup[substr($var_id, 1)]))
                    ) {
                        $codebase = $statements_analyzer->getCodebase();
                        $codebase->analyzer->decrementMixedCount($statements_analyzer->getFilePath());
                    }
                }

                IssueBuffer::remove(
                    $statements_analyzer->getFilePath(),
                    'MixedAssignment',
                    $first_appearance->raw_file_start,
                );
            }
        }
    }

    /**
     * A class that doesn't exist is only known to be there in the block run when `class_exists()` (or the like)
     * is true, and in the elseifs, the else and after the if where the condition is false
     *
     * @throws ScopeAnalysisException
     */
    private static function analyzeCondition(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\If_ $stmt,
        Context $context,
        Codebase $codebase,
        IfScope $if_scope,
        int $branch_point,
    ): IfConditionalScope {
        $phantom_classes_before_cond = $context->phantom_classes;

        $if_conditional_scope = IfConditionalAnalyzer::analyze(
            $statements_analyzer,
            $stmt->cond,
            $context,
            $codebase,
            $if_scope,
            $branch_point,
        );

        $context->phantom_classes = [
            ...$phantom_classes_before_cond,
            ...GuardedClasses::unknownWhenResult($stmt->cond, false, $codebase),
        ];

        // the elseifs and the else run where the condition is false too
        $if_conditional_scope->post_if_context->phantom_classes = $context->phantom_classes;

        return $if_conditional_scope;
    }

    /**
     * Whether the if and every elseif leave, whatever the implicit else does
     *
     * @psalm-capabilities read-props
     */
    private static function everyBranchLeaves(IfScope $if_scope): bool
    {
        // the first of the actions is that of the implicit else
        $branch_actions = array_slice($if_scope->final_actions, 1);

        return $branch_actions && !in_array(ScopeAnalyzer::ACTION_NONE, $branch_actions, true);
    }
}
