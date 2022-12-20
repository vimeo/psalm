<?php

namespace Psalm\Internal\Analyzer\Statements\Block\IfElse;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Algebra;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Scope\IfScope;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\ConflictingReferenceConstraint;
use Psalm\IssueBuffer;
use Psalm\Type\Reconciler;

use function array_diff_key;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function count;
use function in_array;
use function preg_match;
use function preg_quote;

/**
 * @internal
 */
class ElseAnalyzer
{
    /**
     * @return false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        ?PhpParser\Node\Stmt\Else_ $else,
        IfScope $if_scope,
        Context $else_context,
        Context $outer_context
    ): ?bool {
        $codebase = $statements_analyzer->getCodebase();

        if (!$else && !$if_scope->negated_clauses && !$else_context->clauses) {
            $if_scope->final_actions = array_merge([ScopeAnalyzer::ACTION_NONE], $if_scope->final_actions);
            $if_scope->assigned_var_ids = [];
            $if_scope->new_vars = [];
            $if_scope->redefined_vars = [];
            $if_scope->reasonable_clauses = [];

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
                $else
                    ? new CodeLocation($statements_analyzer->getSource(), $else, $outer_context->include_location)
                    : null,
            );

            $else_context->clauses = Context::removeReconciledClauses($else_context->clauses, $changed_var_ids)[0];

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

        foreach ($else_context->parent_remove_vars as $var_id => $_) {
            $outer_context->removeVarFromConflictingClauses($var_id);
        }

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

        // if it doesn't end in a return
        if (!$has_leaving_statements) {
            IfAnalyzer::updateIfScope(
                $codebase,
                $if_scope,
                $else_context,
                $original_context,
                $new_assigned_var_ids,
                $new_possibly_assigned_var_ids,
                [],
                true,
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
                        $if_scope->new_vars_possibly_in_scope = array_merge(
                            $vars_possibly_in_scope,
                            $if_scope->new_vars_possibly_in_scope,
                        );
                    }

                    $else_context->loop_scope->vars_possibly_in_scope = array_merge(
                        $vars_possibly_in_scope,
                        $else_context->loop_scope->vars_possibly_in_scope,
                    );
                }
            } else {
                $if_scope->new_vars_possibly_in_scope = array_merge(
                    $vars_possibly_in_scope,
                    $if_scope->new_vars_possibly_in_scope,
                );

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
}
