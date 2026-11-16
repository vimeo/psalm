<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Block\IfElse;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Algebra;
use Psalm\Internal\Analyzer\AlgebraAnalyzer;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Scope\IfScope;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\ConflictingReferenceConstraint;
use Psalm\IssueBuffer;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\ArrayKeyDoesNotExist;
use Psalm\Storage\Assertion\IsNotIsset;
use Psalm\Type\Reconciler;

use function array_diff_key;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_slice;
use function count;
use function in_array;
use function preg_match;
use function preg_quote;

/**
 * @internal
 */
final class ElseAnalyzer
{
    /**
     * @return false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        ?PhpParser\Node\Stmt\Else_ $else,
        IfScope $if_scope,
        Context $else_context,
        Context $outer_context,
        ?PhpParser\Node\Expr $if_cond = null,
    ): ?bool {
        $codebase = $statements_analyzer->getCodebase();

        if (!$else && !$if_scope->negated_clauses && !$else_context->clauses) {
            $if_scope->final_actions = array_merge([ScopeAnalyzer::ACTION_NONE], $if_scope->final_actions);
            $if_scope->assigned_var_ids = [];
            $if_scope->new_vars = [];
            $if_scope->redefined_vars = [];
            $if_scope->reasonable_clauses = [];
            $if_scope->addBranchReachingTheEnd($else_context);

            return null;
        }

        $else_context->clauses = Algebra::simplifyCNF(
            [...$else_context->clauses, ...$if_scope->negated_clauses],
        );

        $else_types = Algebra::getTruthsFromFormula($else_context->clauses);

        $original_context = clone $else_context;

        if ($else_types) {
            $changed_var_ids = [];

            [$else_context->vars_in_scope, $else_context->references_in_scope] = Reconciler::reconcileKeyedTypes(
                $else_types,
                [],
                $else_context->vars_in_scope,
                $else_context->references_in_scope,
                $changed_var_ids,
                [],
                $statements_analyzer,
                $statements_analyzer->getTemplateTypeMap() ?: [],
                $else_context->inside_loop,
                match (true) {
                    $else !== null => new CodeLocation(
                        $statements_analyzer->getSource(),
                        $else,
                        $outer_context->include_location,
                    ),
                    // no else: the code after the if where its condition is false, like the else IfElseAnalyzer
                    // reconciles first
                    $if_cond !== null && $outer_context->check_variables => new CodeLocation(
                        $statements_analyzer->getSource(),
                        $if_cond instanceof PhpParser\Node\Expr\BooleanNot ? $if_cond->expr : $if_cond,
                        $outer_context->include_location,
                    ),
                    default => null,
                },
            );

            // a native type left without any value proves the conditions matched every value (an offset or a
            // property that may be unset, or is not set, is left as never where it is not set, which is no proof)
            if (!$else) {
                foreach ($changed_var_ids as $changed_var_id => $_) {
                    $original_type = $original_context->vars_in_scope[$changed_var_id] ?? null;

                    if (($else_context->vars_in_scope[$changed_var_id] ?? null)?->isNever()
                        && $original_type !== null
                        && !$original_type->isNever()
                        && !$original_type->from_docblock
                        && !$original_type->possibly_undefined
                        && $original_type->initialized
                        && !self::assertsUnset($else_types[$changed_var_id] ?? [])
                    ) {
                        $if_scope->implicit_else_is_impossible = true;
                        break;
                    }
                }
            }

            $else_context->clauses = AlgebraAnalyzer::removeReconciledClauses(
                $else_context->clauses,
                $changed_var_ids,
                $else_context->vars_in_scope,
                $statements_analyzer,
            )[0];

            foreach ($changed_var_ids as $changed_var_id => $_) {
                foreach ($else_context->vars_in_scope as $var_id => $_) {
                    if (preg_match('/' . preg_quote($changed_var_id, '/') . '[\]\[\-]/', $var_id)
                        && !array_key_exists($var_id, $changed_var_ids)
                    ) {
                        $else_context->removePossibleReference($var_id);
                    }
                }
            }
        }

        $old_else_context = clone $else_context;

        $pre_stmts_assigned_var_ids = $else_context->assigned_var_ids;
        $else_context->assigned_var_ids = [];
        $else_context->fully_assigned_var_ids = [];

        $pre_possibly_assigned_var_ids = $else_context->possibly_assigned_var_ids;
        $else_context->possibly_assigned_var_ids = [];

        if ($else) {
            if ($statements_analyzer->analyze(
                $else->stmts,
                $else_context,
            ) === false
            ) {
                return false;
            }
        }

        $outer_context->removeClausesOfVarsChangedIn($else_context, $statements_analyzer);

        /** @var array<string, int> */
        $new_assigned_var_ids = $else_context->assigned_var_ids;
        $else_context->assigned_var_ids += $pre_stmts_assigned_var_ids;

        /** @var array<string, bool> */
        $new_possibly_assigned_var_ids = $else_context->possibly_assigned_var_ids;
        $else_context->possibly_assigned_var_ids += $pre_possibly_assigned_var_ids;

        if ($else) {
            foreach ($else_context->byref_constraints as $var_id => $byref_constraint) {
                if (isset($outer_context->byref_constraints[$var_id])
                    && ($outer_constraint_type = $outer_context->byref_constraints[$var_id]->type)
                    && $byref_constraint->type
                    && !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $byref_constraint->type,
                        $outer_constraint_type,
                    )
                ) {
                    IssueBuffer::maybeAdd(
                        new ConflictingReferenceConstraint(
                            'There is more than one pass-by-reference constraint on ' . $var_id,
                            new CodeLocation($statements_analyzer, $else, $outer_context->include_location, true),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } else {
                    $outer_context->byref_constraints[$var_id] = $byref_constraint;
                }
            }
        }

        $final_actions = $else
            ? ScopeAnalyzer::getControlActions(
                $else->stmts,
                $statements_analyzer->node_data,
                [],
            )
            : [ScopeAnalyzer::ACTION_NONE];
        // has a return/throw at end
        $has_ending_statements = $final_actions === [ScopeAnalyzer::ACTION_END];
        $has_leaving_statements = $has_ending_statements
            || (count($final_actions) && !in_array(ScopeAnalyzer::ACTION_NONE, $final_actions, true));

        $has_break_statement = $final_actions === [ScopeAnalyzer::ACTION_BREAK];
        $has_continue_statement = $final_actions === [ScopeAnalyzer::ACTION_CONTINUE];

        $if_scope->final_actions = array_merge($final_actions, $if_scope->final_actions);

        $other_branch_actions = array_slice(
            $if_scope->final_actions,
            0,
            count($if_scope->final_actions) - count($if_scope->if_actions),
        );

        if ($if_scope->phantom_classes_of_if_block !== null
            && in_array(ScopeAnalyzer::ACTION_NONE, $if_scope->if_actions, true)
            && !in_array(ScopeAnalyzer::ACTION_NONE, $other_branch_actions, true)
        ) {
            $outer_context->phantom_classes = $if_scope->phantom_classes_of_if_block;
        }

        // if it doesn't end in a return
        if (!$has_leaving_statements) {
            // updateIfScope() gets the context from before the else, not the one the code after the if continues in
            $outer_context->detachReferencesReboundIn($else_context, $statements_analyzer, $else->stmts ?? []);

            // what the conditions of the elseifs assigned is assigned in the else too, even with an unchanged type
            foreach ($if_scope->elseif_cond_assigned_var_ids as $var_id => $_) {
                if (isset($outer_context->vars_in_scope[$var_id])) {
                    $original_context->vars_in_scope[$var_id] = $outer_context->vars_in_scope[$var_id];
                } else {
                    unset($original_context->vars_in_scope[$var_id]);
                }
            }

            IfAnalyzer::updateIfScope(
                $statements_analyzer,
                $if_scope,
                $else_context,
                $original_context,
                $new_assigned_var_ids + $if_scope->elseif_cond_assigned_var_ids,
                $new_possibly_assigned_var_ids,
                $if_scope->if_cond_changed_var_ids,
                true,
                $if_scope->elseif_cond_assigned_var_ids,
            );

            $if_scope->reasonable_clauses = [];
        }

        // update the parent context as necessary
        if ($if_scope->negatable_if_types) {
            $outer_context->update(
                $old_else_context,
                $else_context,
                $has_leaving_statements,
                array_keys($if_scope->negatable_if_types),
                $if_scope->updated_vars,
            );
        }

        if (!$has_ending_statements) {
            $vars_possibly_in_scope = array_diff_key(
                $else_context->vars_possibly_in_scope,
                $outer_context->vars_possibly_in_scope,
            );

            $possibly_assigned_var_ids = $new_possibly_assigned_var_ids;

            if ($has_leaving_statements) {
                if ($else_context->loop_scope) {
                    if (!$has_continue_statement && !$has_break_statement) {
                        $if_scope->new_vars_possibly_in_scope = [
                            ...$vars_possibly_in_scope,
                            ...$if_scope->new_vars_possibly_in_scope,
                        ];
                    }

                    $else_context->loop_scope->vars_possibly_in_scope = [
                        ...$vars_possibly_in_scope,
                        ...$else_context->loop_scope->vars_possibly_in_scope,
                    ];
                }
            } else {
                $if_scope->new_vars_possibly_in_scope = [
                    ...$vars_possibly_in_scope,
                    ...$if_scope->new_vars_possibly_in_scope,
                ];

                $if_scope->possibly_assigned_var_ids = array_merge(
                    $possibly_assigned_var_ids,
                    $if_scope->possibly_assigned_var_ids,
                );
            }
        }

        if ($outer_context->collect_exceptions) {
            $outer_context->mergeExceptions($else_context);
        }

        // Track references set in the else to make sure they aren't reused later
        $outer_context->updateReferencesPossiblyFromConfusingScope(
            $else_context,
            $statements_analyzer,
        );

        return null;
    }

    /**
     * @param list<list<Assertion>> $assertions
     * @psalm-pure
     */
    private static function assertsUnset(array $assertions): bool
    {
        foreach ($assertions as $or_assertions) {
            foreach ($or_assertions as $assertion) {
                if ($assertion instanceof IsNotIsset || $assertion instanceof ArrayKeyDoesNotExist) {
                    return true;
                }
            }
        }

        return false;
    }
}
