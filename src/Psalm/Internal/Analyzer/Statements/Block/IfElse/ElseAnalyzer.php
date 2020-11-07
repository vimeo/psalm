<?php
namespace Psalm\Internal\Analyzer\Statements\Block\IfElse;

use PhpParser;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ConflictingReferenceConstraint;
use Psalm\IssueBuffer;
use Psalm\Internal\Scope\IfScope;
use Psalm\Type;
use Psalm\Internal\Algebra;
use Psalm\Type\Reconciler;
use function array_merge;
use function array_diff_key;
use function array_keys;
use function count;
use function in_array;
use function array_intersect_key;

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
            array_merge(
                $else_context->clauses,
                $if_scope->negated_clauses
            )
        );

        $else_types = Algebra::getTruthsFromFormula($else_context->clauses);

        if (!$else && !$else_types) {
            $if_scope->final_actions = array_merge([ScopeAnalyzer::ACTION_NONE], $if_scope->final_actions);
            $if_scope->assigned_var_ids = [];
            $if_scope->new_vars = [];
            $if_scope->redefined_vars = [];
            $if_scope->reasonable_clauses = [];

            return null;
        }

        $original_context = clone $else_context;

        if ($else_types) {
            $changed_var_ids = [];

            $else_vars_reconciled = Reconciler::reconcileKeyedTypes(
                $else_types,
                [],
                $else_context->vars_in_scope,
                $changed_var_ids,
                [],
                $statements_analyzer,
                [],
                $else_context->inside_loop,
                $else
                    ? new CodeLocation($statements_analyzer->getSource(), $else, $outer_context->include_location)
                    : null
            );

            $else_context->vars_in_scope = $else_vars_reconciled;

            $else_context->clauses = Context::removeReconciledClauses($else_context->clauses, $changed_var_ids)[0];
        }

        $old_else_context = clone $else_context;

        $pre_stmts_assigned_var_ids = $else_context->assigned_var_ids;
        $else_context->assigned_var_ids = [];

        $pre_possibly_assigned_var_ids = $else_context->possibly_assigned_var_ids;
        $else_context->possibly_assigned_var_ids = [];

        if ($else) {
            if ($statements_analyzer->analyze(
                $else->stmts,
                $else_context
            ) === false
            ) {
                return false;
            }
        }

        /** @var array<string, int> */
        $new_assigned_var_ids = $else_context->assigned_var_ids;
        $else_context->assigned_var_ids = $pre_stmts_assigned_var_ids;

        /** @var array<string, bool> */
        $new_possibly_assigned_var_ids = $else_context->possibly_assigned_var_ids;
        $else_context->possibly_assigned_var_ids = $pre_possibly_assigned_var_ids + $new_possibly_assigned_var_ids;

        if ($else) {
            foreach ($else_context->byref_constraints as $var_id => $byref_constraint) {
                if (isset($outer_context->byref_constraints[$var_id])
                    && ($outer_constraint_type = $outer_context->byref_constraints[$var_id]->type)
                    && $byref_constraint->type
                    && !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $byref_constraint->type,
                        $outer_constraint_type
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new ConflictingReferenceConstraint(
                            'There is more than one pass-by-reference constraint on ' . $var_id,
                            new CodeLocation($statements_analyzer, $else, $outer_context->include_location, true)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    $outer_context->byref_constraints[$var_id] = $byref_constraint;
                }
            }
        }

        $final_actions = $else
            ? ScopeAnalyzer::getControlActions(
                $else->stmts,
                $statements_analyzer->node_data,
                $codebase->config->exit_functions,
                $outer_context->break_types
            )
            : [ScopeAnalyzer::ACTION_NONE];
        // has a return/throw at end
        $has_ending_statements = $final_actions === [ScopeAnalyzer::ACTION_END];
        $has_leaving_statements = $has_ending_statements
            || (count($final_actions) && !in_array(ScopeAnalyzer::ACTION_NONE, $final_actions, true));

        $has_break_statement = $final_actions === [ScopeAnalyzer::ACTION_BREAK];
        $has_continue_statement = $final_actions === [ScopeAnalyzer::ACTION_CONTINUE];

        $if_scope->final_actions = array_merge($final_actions, $if_scope->final_actions);

        $else_redefined_vars = $else_context->getRedefinedVars($original_context->vars_in_scope);

        // if it doesn't end in a return
        if (!$has_leaving_statements) {
            if ($if_scope->new_vars === null && $else) {
                $if_scope->new_vars = array_diff_key($else_context->vars_in_scope, $outer_context->vars_in_scope);
            } elseif ($if_scope->new_vars !== null) {
                foreach ($if_scope->new_vars as $new_var => $type) {
                    if (!$else_context->hasVariable($new_var)) {
                        unset($if_scope->new_vars[$new_var]);
                    } else {
                        $if_scope->new_vars[$new_var] = Type::combineUnionTypes(
                            $type,
                            $else_context->vars_in_scope[$new_var],
                            $codebase
                        );
                    }
                }
            }

            if ($if_scope->assigned_var_ids === null) {
                $if_scope->assigned_var_ids = $new_assigned_var_ids;
            } else {
                $if_scope->assigned_var_ids = array_intersect_key($new_assigned_var_ids, $if_scope->assigned_var_ids);
            }

            if ($if_scope->redefined_vars === null) {
                $if_scope->redefined_vars = $else_redefined_vars;
                $if_scope->possibly_redefined_vars = $if_scope->redefined_vars;
            } else {
                foreach ($if_scope->redefined_vars as $redefined_var => $type) {
                    if (!isset($else_redefined_vars[$redefined_var])) {
                        unset($if_scope->redefined_vars[$redefined_var]);
                    } else {
                        $if_scope->redefined_vars[$redefined_var] = Type::combineUnionTypes(
                            $else_redefined_vars[$redefined_var],
                            $type,
                            $codebase
                        );
                    }
                }

                foreach ($else_redefined_vars as $var => $type) {
                    if (isset($if_scope->possibly_redefined_vars[$var])) {
                        $if_scope->possibly_redefined_vars[$var] = Type::combineUnionTypes(
                            $type,
                            $if_scope->possibly_redefined_vars[$var],
                            $codebase
                        );
                    } else {
                        $if_scope->possibly_redefined_vars[$var] = $type;
                    }
                }
            }

            $if_scope->reasonable_clauses = [];
        }

        // update the parent context as necessary
        if ($if_scope->negatable_if_types) {
            $outer_context->update(
                $old_else_context,
                $else_context,
                $has_leaving_statements,
                array_keys($if_scope->negatable_if_types),
                $if_scope->updated_vars
            );
        }

        if (!$has_ending_statements) {
            $vars_possibly_in_scope = array_diff_key(
                $else_context->vars_possibly_in_scope,
                $outer_context->vars_possibly_in_scope
            );

            $possibly_assigned_var_ids = $new_possibly_assigned_var_ids;

            if ($has_leaving_statements && $else_context->loop_scope) {
                if (!$has_continue_statement && !$has_break_statement) {
                    $if_scope->new_vars_possibly_in_scope = array_merge(
                        $vars_possibly_in_scope,
                        $if_scope->new_vars_possibly_in_scope
                    );

                    $if_scope->possibly_assigned_var_ids = array_merge(
                        $possibly_assigned_var_ids,
                        $if_scope->possibly_assigned_var_ids
                    );
                }

                $else_context->loop_scope->vars_possibly_in_scope = array_merge(
                    $vars_possibly_in_scope,
                    $else_context->loop_scope->vars_possibly_in_scope
                );
            } elseif (!$has_leaving_statements) {
                $if_scope->new_vars_possibly_in_scope = array_merge(
                    $vars_possibly_in_scope,
                    $if_scope->new_vars_possibly_in_scope
                );

                $if_scope->possibly_assigned_var_ids = array_merge(
                    $possibly_assigned_var_ids,
                    $if_scope->possibly_assigned_var_ids
                );
            }
        }

        if ($outer_context->collect_exceptions) {
            $outer_context->mergeExceptions($else_context);
        }

        return null;
    }
}
