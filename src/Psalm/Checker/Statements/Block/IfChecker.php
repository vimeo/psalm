<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\AlgebraChecker;
use Psalm\Checker\ScopeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\Clause;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ConflictingReferenceConstraint;
use Psalm\IssueBuffer;
use Psalm\Scope\IfScope;
use Psalm\Scope\LoopScope;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Reconciler;

class IfChecker
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
     * @param  StatementsChecker       $statements_checker
     * @param  PhpParser\Node\Stmt\If_ $stmt
     * @param  Context                 $context
     *
     * @return null|false
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\If_ $stmt,
        Context $context,
        LoopScope $loop_scope = null
    ) {
        // get the first expression in the if, which should be evaluated on its own
        // this allows us to update the context of $matches in
        // if (!preg_match('/a/', 'aa', $matches)) {
        //   exit
        // }
        // echo $matches[0];
        $first_if_cond_expr = self::getDefinitelyEvaluatedExpression($stmt->cond);

        $context->inside_conditional = true;

        $pre_condition_vars_in_scope = $context->vars_in_scope;

        $referenced_var_ids = $context->referenced_var_ids;
        $context->referenced_var_ids = [];

        $pre_assigned_var_ids = $context->assigned_var_ids;
        $context->assigned_var_ids = [];

        if ($first_if_cond_expr &&
            ExpressionChecker::analyze($statements_checker, $first_if_cond_expr, $context) === false
        ) {
            return false;
        }

        $first_cond_assigned_var_ids = $context->assigned_var_ids;
        $context->assigned_var_ids = array_merge(
            $pre_assigned_var_ids,
            $first_cond_assigned_var_ids
        );

        $first_cond_referenced_var_ids = $context->referenced_var_ids;
        $context->referenced_var_ids = array_merge(
            $referenced_var_ids,
            $first_cond_referenced_var_ids
        );

        $context->inside_conditional = false;

        $if_scope = new IfScope();

        $if_context = clone $context;

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        if ($project_checker->alter_code) {
            $if_context->branch_point = $if_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
        }

        // we need to clone the current context so our ongoing updates to $context don't mess with elseif/else blocks
        $original_context = clone $context;

        $if_context->inside_conditional = true;

        $assigned_var_ids = $context->assigned_var_ids;
        $if_context->assigned_var_ids = [];

        $referenced_var_ids = $context->referenced_var_ids;
        $if_context->referenced_var_ids = [];

        if ($first_if_cond_expr !== $stmt->cond &&
            ExpressionChecker::analyze($statements_checker, $stmt->cond, $if_context) === false
        ) {
            return false;
        }

        /** @var array<string, bool> */
        $more_cond_referenced_var_ids = $if_context->referenced_var_ids;
        $if_context->referenced_var_ids = array_merge(
            $more_cond_referenced_var_ids,
            $referenced_var_ids
        );

        $cond_referenced_var_ids = array_merge(
            $first_cond_referenced_var_ids,
            $more_cond_referenced_var_ids
        );

        /** @var array<string, bool> */
        $more_cond_assigned_var_ids = $if_context->assigned_var_ids;
        $if_context->assigned_var_ids = array_merge(
            $more_cond_assigned_var_ids,
            $assigned_var_ids
        );

        $cond_assigned_var_ids = array_merge(
            $first_cond_assigned_var_ids,
            $more_cond_assigned_var_ids
        );

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
                $if_context->vars_in_scope,
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

        $if_context->inside_conditional = false;

        $mixed_var_ids = [];

        foreach ($if_context->vars_in_scope as $var_id => $type) {
            if ($type->isMixed()) {
                $mixed_var_ids[] = $var_id;
            }
        }

        $if_clauses = Algebra::getFormula(
            $stmt->cond,
            $context->self,
            $statements_checker
        );

        $if_clauses = array_values(
            array_map(
                /**
                 * @return Clause
                 */
                function (Clause $c) use ($mixed_var_ids) {
                    $keys = array_keys($c->possibilities);

                    foreach ($keys as $key) {
                        foreach ($mixed_var_ids as $mixed_var_id) {
                            if (preg_match('/^' . preg_quote($mixed_var_id, '/') . '(\[|-)/', $key)) {
                                return new Clause([], true);
                            }
                        }
                    }

                    return $c;
                },
                $if_clauses
            )
        );

        // this will see whether any of the clauses in set A conflict with the clauses in set B
        AlgebraChecker::checkForParadox(
            $context->clauses,
            $if_clauses,
            $statements_checker,
            $stmt->cond,
            $cond_assigned_var_ids
        );

        // if we have assignments in the if, we may have duplicate clauses
        if ($cond_assigned_var_ids) {
            $if_clauses = Algebra::simplifyCNF($if_clauses);
        }

        $if_context->clauses = Algebra::simplifyCNF(array_merge($context->clauses, $if_clauses));

        // define this before we alter local claues after reconciliation
        $if_scope->reasonable_clauses = $if_context->clauses;

        $if_scope->negated_clauses = Algebra::negateFormula($if_clauses);

        $if_scope->negated_types = Algebra::getTruthsFromFormula(
            Algebra::simplifyCNF(
                array_merge($context->clauses, $if_scope->negated_clauses)
            )
        );

        $reconcilable_if_types = Algebra::getTruthsFromFormula(
            $if_context->clauses,
            $cond_referenced_var_ids
        );

        // if the if has an || in the conditional, we cannot easily reason about it
        if ($reconcilable_if_types) {
            $changed_var_ids = [];

            $if_vars_in_scope_reconciled =
                Reconciler::reconcileKeyedTypes(
                    $reconcilable_if_types,
                    $if_context->vars_in_scope,
                    $changed_var_ids,
                    $cond_referenced_var_ids,
                    $statements_checker,
                    $context->check_variables
                        ? new CodeLocation(
                            $statements_checker->getSource(),
                            $stmt->cond,
                            $context->include_location
                        ) : null,
                    $statements_checker->getSuppressedIssues()
                );

            $if_context->vars_in_scope = $if_vars_in_scope_reconciled;

            foreach ($reconcilable_if_types as $var_id => $_) {
                $if_context->vars_possibly_in_scope[$var_id] = true;
            }

            if ($changed_var_ids) {
                $if_context->removeReconciledClauses($changed_var_ids);
            }

            $if_scope->if_cond_changed_var_ids = $changed_var_ids;
        }

        $old_if_context = clone $if_context;
        $context->vars_possibly_in_scope = array_merge(
            $if_context->vars_possibly_in_scope,
            $context->vars_possibly_in_scope
        );

        $context->referenced_var_ids = array_merge(
            $if_context->referenced_var_ids,
            $context->referenced_var_ids
        );

        $temp_else_context = clone $original_context;

        $changed_var_ids = [];

        if ($if_scope->negated_types) {
            $else_vars_reconciled = Reconciler::reconcileKeyedTypes(
                $if_scope->negated_types,
                $temp_else_context->vars_in_scope,
                $changed_var_ids,
                $stmt->else || $stmt->elseifs ? $cond_referenced_var_ids : [],
                $statements_checker,
                $context->check_variables
                    ? new CodeLocation(
                        $statements_checker->getSource(),
                        $stmt->cond,
                        $context->include_location
                    ) : null,
                $statements_checker->getSuppressedIssues()
            );

            $temp_else_context->vars_in_scope = $else_vars_reconciled;
        }

        // we calculate the vars redefined in a hypothetical else statement to determine
        // which vars of the if we can safely change
        $pre_assignment_else_redefined_vars = $temp_else_context->getRedefinedVars($context->vars_in_scope, true);

        $old_unreferenced_vars = $context->unreferenced_vars;
        $newly_unreferenced_locations = [];

        // this captures statements in the if conditional
        if ($context->collect_references) {
            foreach ($if_context->unreferenced_vars as $var_id => $location) {
                if (!isset($old_unreferenced_vars[$var_id])
                    || $old_unreferenced_vars[$var_id] !== $location
                ) {
                    $newly_unreferenced_locations[$var_id][] = $location;
                }
            }
        }

        // check the if
        if (self::analyzeIfBlock(
            $statements_checker,
            $stmt,
            $if_scope,
            $if_context,
            $old_if_context,
            $context,
            $pre_assignment_else_redefined_vars,
            $loop_scope
        ) === false) {
            return false;
        }

        if ($context->collect_references && $if_scope->final_actions !== [ScopeChecker::ACTION_END]) {
            foreach ($if_context->unreferenced_vars as $var_id => $location) {
                if (!isset($old_unreferenced_vars[$var_id])
                    || $old_unreferenced_vars[$var_id] !== $location
                ) {
                    $newly_unreferenced_locations[$var_id][] = $location;
                }
            }
        }

        // check the elseifs
        foreach ($stmt->elseifs as $elseif) {
            $elseif_context = clone $original_context;

            if ($project_checker->alter_code) {
                $elseif_context->branch_point =
                    $elseif_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
            }

            if (self::analyzeElseIfBlock(
                $statements_checker,
                $elseif,
                $if_scope,
                $elseif_context,
                $context,
                $loop_scope
            ) === false) {
                return false;
            }

            if ($context->collect_references && $if_scope->final_actions !== [ScopeChecker::ACTION_END]) {
                foreach ($elseif_context->unreferenced_vars as $var_id => $location) {
                    if (!isset($old_unreferenced_vars[$var_id])
                        || $old_unreferenced_vars[$var_id] !== $location
                    ) {
                        $newly_unreferenced_locations[$var_id][] = $location;
                    }
                }
            }
        }

        // check the else
        if ($stmt->else) {
            $else_context = clone $original_context;

            if ($project_checker->alter_code) {
                $else_context->branch_point =
                    $else_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
            }

            if (self::analyzeElseBlock(
                $statements_checker,
                $stmt->else,
                $if_scope,
                $else_context,
                $context,
                $loop_scope
            ) === false) {
                return false;
            }

            if ($context->collect_references && $if_scope->final_actions !== [ScopeChecker::ACTION_END]) {
                foreach ($else_context->unreferenced_vars as $var_id => $location) {
                    if (!isset($old_unreferenced_vars[$var_id])
                        || $old_unreferenced_vars[$var_id] !== $location
                    ) {
                        $newly_unreferenced_locations[$var_id][] = $location;
                    }
                }
            }
        } else {
            $if_scope->final_actions[] = ScopeChecker::ACTION_NONE;
        }

        if ($loop_scope) {
            $loop_scope->final_actions = array_unique(
                array_merge(
                    $loop_scope->final_actions,
                    $if_scope->final_actions
                )
            );
        }

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $if_scope->new_vars_possibly_in_scope
        );

        $context->possibly_assigned_var_ids = array_merge(
            $context->possibly_assigned_var_ids,
            $if_scope->possibly_assigned_var_ids ?: []
        );

        // vars can only be defined/redefined if there was an else (defined in every block)
        if ($stmt->else) {
            $context->assigned_var_ids = array_merge(
                $context->assigned_var_ids,
                $if_scope->assigned_var_ids ?: []
            );

            if ($if_scope->new_vars) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $if_scope->new_vars);
            }

            if ($if_scope->redefined_vars) {
                foreach ($if_scope->redefined_vars as $var_id => $type) {
                    $context->vars_in_scope[$var_id] = $type;
                    $if_scope->updated_vars[$var_id] = true;

                    if ($if_scope->reasonable_clauses) {
                        $if_scope->reasonable_clauses = Context::filterClauses(
                            $var_id,
                            $if_scope->reasonable_clauses,
                            isset($context->vars_in_scope[$var_id])
                                ? $context->vars_in_scope[$var_id]
                                : null,
                            $statements_checker
                        );
                    }
                }
            }

            if ($if_scope->possible_param_types) {
                foreach ($if_scope->possible_param_types as $var => $type) {
                    $context->possible_param_types[$var] = $type;
                }
            }

            if ($if_scope->reasonable_clauses
                && (count($if_scope->reasonable_clauses) > 1 || !$if_scope->reasonable_clauses[0]->wedge)
            ) {
                $context->clauses = Algebra::simplifyCNF(
                    array_merge(
                        $if_scope->reasonable_clauses,
                        $context->clauses
                    )
                );
            }
        } else {
            if ($loop_scope && !in_array(ScopeChecker::ACTION_NONE, $if_scope->final_actions, true)) {
                $loop_scope->redefined_loop_vars = null;
            }
        }

        if ($if_scope->possibly_redefined_vars) {
            foreach ($if_scope->possibly_redefined_vars as $var_id => $type) {
                if ($context->hasVariable($var_id)
                    && !$type->failed_reconciliation
                    && !isset($if_scope->updated_vars[$var_id])
                ) {
                    $combined_type = Type::combineUnionTypes(
                        $context->vars_in_scope[$var_id],
                        $type
                    );

                    if ($combined_type->equals($context->vars_in_scope[$var_id])) {
                        continue;
                    }

                    $context->removeDescendents($var_id, $combined_type);
                    $context->vars_in_scope[$var_id] = $combined_type;
                }
            }
        }

        if ($context->collect_references) {
            foreach ($newly_unreferenced_locations as $var_id => $locations) {
                if (($stmt->else
                        && (isset($if_scope->assigned_var_ids[$var_id]) || isset($if_scope->new_vars[$var_id])))
                    || !isset($context->vars_in_scope[$var_id])
                ) {
                    $context->unreferenced_vars[$var_id] = array_shift($locations);
                }

                foreach ($locations as $location) {
                    $statements_checker->registerVariableUse($location);
                }
            }
        }

        return null;
    }

    /**
     * @param  StatementsChecker        $statements_checker
     * @param  PhpParser\Node\Stmt\If_  $stmt
     * @param  IfScope                  $if_scope
     * @param  Context                  $if_context
     * @param  Context                  $old_if_context
     * @param  Context                  $outer_context
     * @param  array<string,Type\Union> $pre_assignment_else_redefined_vars
     *
     * @return false|null
     */
    protected static function analyzeIfBlock(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\If_ $stmt,
        IfScope $if_scope,
        Context $if_context,
        Context $old_if_context,
        Context $outer_context,
        array $pre_assignment_else_redefined_vars,
        LoopScope $loop_scope = null
    ) {
        $final_actions = ScopeChecker::getFinalControlActions($stmt->stmts);

        $has_ending_statements = $final_actions === [ScopeChecker::ACTION_END];

        $has_leaving_statements = $has_ending_statements
            || (count($final_actions) && !in_array(ScopeChecker::ACTION_NONE, $final_actions, true));

        $has_break_statement = $final_actions === [ScopeChecker::ACTION_BREAK];
        $has_continue_statement = $final_actions === [ScopeChecker::ACTION_CONTINUE];

        if (!$has_ending_statements) {
            $if_context->parent_context = $outer_context;
        }

        $if_scope->final_actions = $final_actions;

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $assigned_var_ids = $if_context->assigned_var_ids;
        $possibly_assigned_var_ids = $if_context->possibly_assigned_var_ids;
        $if_context->assigned_var_ids = [];
        $if_context->possibly_assigned_var_ids = [];

        if ($statements_checker->analyze(
            $stmt->stmts,
            $if_context,
            $loop_scope
        ) === false
        ) {
            return false;
        }

        /** @var array<string, bool> */
        $new_assigned_var_ids = $if_context->assigned_var_ids;
        /** @var array<string, bool> */
        $new_possibly_assigned_var_ids = $if_context->possibly_assigned_var_ids;

        $if_context->assigned_var_ids = array_merge($assigned_var_ids, $new_assigned_var_ids);
        $if_context->possibly_assigned_var_ids = array_merge(
            $possibly_assigned_var_ids,
            $new_possibly_assigned_var_ids
        );

        if ($if_context->byref_constraints !== null) {
            foreach ($if_context->byref_constraints as $var_id => $byref_constraint) {
                if ($outer_context->byref_constraints !== null
                    && isset($outer_context->byref_constraints[$var_id])
                    && $byref_constraint->type
                    && ($outer_constraint_type = $outer_context->byref_constraints[$var_id]->type)
                    && !TypeChecker::isContainedBy(
                        $project_checker->codebase,
                        $byref_constraint->type,
                        $outer_constraint_type
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new ConflictingReferenceConstraint(
                            'There is more than one pass-by-reference constraint on ' . $var_id,
                            new CodeLocation($statements_checker, $stmt, $outer_context->include_location, true)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    $outer_context->byref_constraints[$var_id] = $byref_constraint;
                }
            }
        }

        if ($outer_context->collect_references) {
            $outer_context->referenced_var_ids = array_merge(
                $outer_context->referenced_var_ids,
                $if_context->referenced_var_ids
            );
        }

        $mic_drop = false;

        if (!$has_leaving_statements) {
            $if_scope->new_vars = array_diff_key($if_context->vars_in_scope, $outer_context->vars_in_scope);

            $if_scope->redefined_vars = $if_context->getRedefinedVars($outer_context->vars_in_scope);
            $if_scope->possibly_redefined_vars = $if_scope->redefined_vars;
            $if_scope->assigned_var_ids = $new_assigned_var_ids;
            $if_scope->possibly_assigned_var_ids = $new_possibly_assigned_var_ids;

            $changed_var_ids = array_keys($new_assigned_var_ids);

            // if the variable was only set in the conditional, it's not possibly redefined
            foreach ($if_scope->possibly_redefined_vars as $var_id => $_) {
                if (!isset($new_possibly_assigned_var_ids[$var_id])
                    && in_array($var_id, $if_scope->if_cond_changed_var_ids, true)
                ) {
                    unset($if_scope->possibly_redefined_vars[$var_id]);
                }
            }

            if ($if_scope->reasonable_clauses) {
                // remove all reasonable clauses that would be negated by the if stmts
                foreach ($changed_var_ids as $var_id) {
                    $if_scope->reasonable_clauses = Context::filterClauses(
                        $var_id,
                        $if_scope->reasonable_clauses,
                        isset($if_context->vars_in_scope[$var_id]) ? $if_context->vars_in_scope[$var_id] : null,
                        $statements_checker
                    );
                }
            }

            if ($project_checker->infer_types_from_usage) {
                $if_scope->possible_param_types = $if_context->possible_param_types;
            }
        } else {
            if (!$has_break_statement) {
                $if_scope->reasonable_clauses = [];
            }
        }

        if ($has_leaving_statements && !$has_break_statement && !$stmt->else && !$stmt->elseifs) {
            if ($if_scope->negated_types) {
                $changed_var_ids = [];

                $outer_context_vars_reconciled = Reconciler::reconcileKeyedTypes(
                    $if_scope->negated_types,
                    $outer_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_checker,
                    new CodeLocation(
                        $statements_checker->getSource(),
                        $stmt->cond,
                        $outer_context->include_location,
                        false
                    ),
                    $statements_checker->getSuppressedIssues()
                );

                foreach ($changed_var_ids as $changed_var_id) {
                    $outer_context->removeVarFromConflictingClauses($changed_var_id);
                }

                $changed_var_ids = array_unique(
                    array_merge(
                        $changed_var_ids,
                        array_keys($new_assigned_var_ids)
                    )
                );

                foreach ($changed_var_ids as $var_id) {
                    $if_scope->negated_clauses = Context::filterClauses(
                        $var_id,
                        $if_scope->negated_clauses
                    );
                }

                $outer_context->vars_in_scope = $outer_context_vars_reconciled;
                $mic_drop = true;
            }

            $outer_context->clauses = Algebra::simplifyCNF(
                array_merge($outer_context->clauses, $if_scope->negated_clauses)
            );
        }

        // update the parent context as necessary, but only if we can safely reason about type negation.
        // We only update vars that changed both at the start of the if block and then again by an assignment
        // in the if statement.
        if ($if_scope->negated_types && !$mic_drop) {
            $vars_to_update = array_intersect(
                array_keys($pre_assignment_else_redefined_vars),
                array_keys($if_scope->negated_types)
            );

            $extra_vars_to_update = [];

            // if there's an object-like array in there, we also need to update the root array variable
            foreach ($vars_to_update as $var_id) {
                $bracked_pos = strpos($var_id, '[');
                if ($bracked_pos !== false) {
                    $extra_vars_to_update[] = substr($var_id, 0, $bracked_pos);
                }
            }

            if ($extra_vars_to_update) {
                $vars_to_update = array_unique(array_merge($extra_vars_to_update, $vars_to_update));
            }

            //update $if_context vars to include the pre-assignment else vars
            if (!$stmt->else && !$has_leaving_statements) {
                foreach ($pre_assignment_else_redefined_vars as $var_id => $type) {
                    if (isset($if_context->vars_in_scope[$var_id])) {
                        $if_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $if_context->vars_in_scope[$var_id],
                            $type
                        );
                    }
                }
            }

            $outer_context->update(
                $old_if_context,
                $if_context,
                $has_leaving_statements,
                $vars_to_update,
                $if_scope->updated_vars
            );
        }

        if (!$has_ending_statements) {
            $vars_possibly_in_scope = array_diff_key(
                $if_context->vars_possibly_in_scope,
                $outer_context->vars_possibly_in_scope
            );

            $possibly_assigned_var_ids = array_diff_key(
                $if_context->possibly_assigned_var_ids,
                $outer_context->possibly_assigned_var_ids
            );

            if ($loop_scope) {
                if (!$has_continue_statement && !$has_break_statement) {
                    $if_scope->new_vars_possibly_in_scope = $vars_possibly_in_scope;
                    $if_scope->possibly_assigned_var_ids = $possibly_assigned_var_ids;
                }

                $loop_scope->vars_possibly_in_scope = array_merge(
                    $vars_possibly_in_scope,
                    $loop_scope->vars_possibly_in_scope
                );
            } elseif (!$has_leaving_statements) {
                $if_scope->new_vars_possibly_in_scope = $vars_possibly_in_scope;
            }
        }
    }

    /**
     * @param  StatementsChecker           $statements_checker
     * @param  PhpParser\Node\Stmt\ElseIf_ $elseif
     * @param  IfScope                     $if_scope
     * @param  Context                     $elseif_context
     * @param  Context                     $outer_context
     *
     * @return false|null
     */
    protected static function analyzeElseIfBlock(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\ElseIf_ $elseif,
        IfScope $if_scope,
        Context $elseif_context,
        Context $outer_context,
        LoopScope $loop_scope = null
    ) {
        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $original_context = clone $elseif_context;

        $entry_clauses = array_merge($original_context->clauses, $if_scope->negated_clauses);

        $changed_var_ids = [];

        if ($if_scope->negated_types) {
            $elseif_vars_reconciled = Reconciler::reconcileKeyedTypes(
                $if_scope->negated_types,
                $elseif_context->vars_in_scope,
                $changed_var_ids,
                [],
                $statements_checker,
                new CodeLocation(
                    $statements_checker->getSource(),
                    $elseif->cond,
                    $outer_context->include_location,
                    false
                ),
                $statements_checker->getSuppressedIssues()
            );

            $elseif_context->vars_in_scope = $elseif_vars_reconciled;

            if ($changed_var_ids) {
                $entry_clauses = array_filter(
                    $entry_clauses,
                    /** @return bool */
                    function (Clause $c) use ($changed_var_ids) {
                        return count($c->possibilities) > 1
                            || $c->wedge
                            || !in_array(array_keys($c->possibilities)[0], $changed_var_ids, true);
                    }
                );
            }
        }

        $pre_conditional_context = clone $elseif_context;

        $elseif_context->inside_conditional = true;

        $pre_assigned_var_ids = $elseif_context->assigned_var_ids;

        $referenced_var_ids = $elseif_context->referenced_var_ids;
        $elseif_context->referenced_var_ids = [];

        // check the elseif
        if (ExpressionChecker::analyze($statements_checker, $elseif->cond, $elseif_context) === false) {
            return false;
        }

        $new_referenced_var_ids = $elseif_context->referenced_var_ids;
        $elseif_context->referenced_var_ids = array_merge(
            $referenced_var_ids,
            $elseif_context->referenced_var_ids
        );

        $conditional_assigned_var_ids = $elseif_context->assigned_var_ids;

        $elseif_context->assigned_var_ids = array_merge($pre_assigned_var_ids, $conditional_assigned_var_ids);

        $new_assigned_var_ids = array_diff_key($conditional_assigned_var_ids, $pre_assigned_var_ids);

        $new_referenced_var_ids = array_diff_key($new_referenced_var_ids, $new_assigned_var_ids);

        $elseif_context->inside_conditional = false;

        $mixed_var_ids = [];

        foreach ($elseif_context->vars_in_scope as $var_id => $type) {
            if ($type->isMixed()) {
                $mixed_var_ids[] = $var_id;
            }
        }

        $elseif_clauses = Algebra::getFormula(
            $elseif->cond,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $elseif_clauses = array_map(
            /**
             * @return Clause
             */
            function (Clause $c) use ($mixed_var_ids) {
                $keys = array_keys($c->possibilities);

                foreach ($keys as $key) {
                    foreach ($mixed_var_ids as $mixed_var_id) {
                        if (preg_match('/^' . preg_quote($mixed_var_id, '/') . '(\[|-)/', $key)) {
                            return new Clause([], true);
                        }
                    }
                }

                return $c;
            },
            $elseif_clauses
        );

        $entry_clauses = array_map(
            /**
             * @return Clause
             */
            function (Clause $c) use ($conditional_assigned_var_ids) {
                $keys = array_keys($c->possibilities);

                foreach ($keys as $key) {
                    foreach ($conditional_assigned_var_ids as $conditional_assigned_var_id => $_) {
                        if (preg_match('/^' . preg_quote($conditional_assigned_var_id, '/') . '(\[|-|$)/', $key)) {
                            return new Clause([], true);
                        }
                    }
                }

                return $c;
            },
            $entry_clauses
        );

        // this will see whether any of the clauses in set A conflict with the clauses in set B
        AlgebraChecker::checkForParadox(
            $entry_clauses,
            $elseif_clauses,
            $statements_checker,
            $elseif->cond,
            $new_assigned_var_ids
        );

        $elseif_context->clauses = Algebra::simplifyCNF(
            array_merge(
                $entry_clauses,
                $elseif_clauses
            )
        );

        $reconcilable_elseif_types = Algebra::getTruthsFromFormula($elseif_context->clauses);
        $negated_elseif_types = Algebra::getTruthsFromFormula(Algebra::negateFormula($elseif_clauses));

        $all_negated_vars = array_unique(
            array_merge(
                array_keys($negated_elseif_types),
                array_keys($if_scope->negated_types)
            )
        );

        foreach ($all_negated_vars as $var_id) {
            if (isset($negated_elseif_types[$var_id])) {
                if (isset($if_scope->negated_types[$var_id])) {
                    $if_scope->negated_types[$var_id] = array_merge(
                        $if_scope->negated_types[$var_id],
                        $negated_elseif_types[$var_id]
                    );
                } else {
                    $if_scope->negated_types[$var_id] = $negated_elseif_types[$var_id];
                }
            }
        }

        $changed_var_ids = $changed_var_ids ?: [];

        // if the elseif has an || in the conditional, we cannot easily reason about it
        if ($reconcilable_elseif_types) {
            $elseif_vars_reconciled = Reconciler::reconcileKeyedTypes(
                $reconcilable_elseif_types,
                $elseif_context->vars_in_scope,
                $changed_var_ids,
                $new_referenced_var_ids,
                $statements_checker,
                new CodeLocation($statements_checker->getSource(), $elseif->cond, $outer_context->include_location),
                $statements_checker->getSuppressedIssues()
            );

            $elseif_context->vars_in_scope = $elseif_vars_reconciled;

            if ($changed_var_ids) {
                $elseif_context->removeReconciledClauses($changed_var_ids);
            }
        }

        $old_elseif_context = clone $elseif_context;

        $pre_stmts_assigned_var_ids = $elseif_context->assigned_var_ids;
        $elseif_context->assigned_var_ids = [];

        if ($statements_checker->analyze(
            $elseif->stmts,
            $elseif_context,
            $loop_scope
        ) === false
        ) {
            return false;
        }

        /** @var array<string, bool> */
        $new_stmts_assigned_var_ids = $elseif_context->assigned_var_ids;
        $elseif_context->assigned_var_ids = $pre_stmts_assigned_var_ids;

        if ($elseif_context->byref_constraints !== null) {
            foreach ($elseif_context->byref_constraints as $var_id => $byref_constraint) {
                if ($outer_context->byref_constraints !== null
                    && isset($outer_context->byref_constraints[$var_id])
                    && ($outer_constraint_type = $outer_context->byref_constraints[$var_id]->type)
                    && $byref_constraint->type
                    && !TypeChecker::isContainedBy(
                        $project_checker->codebase,
                        $byref_constraint->type,
                        $outer_constraint_type
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new ConflictingReferenceConstraint(
                            'There is more than one pass-by-reference constraint on ' . $var_id,
                            new CodeLocation($statements_checker, $elseif, $outer_context->include_location, true)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    $outer_context->byref_constraints[$var_id] = $byref_constraint;
                }
            }
        }

        $final_actions = ScopeChecker::getFinalControlActions($elseif->stmts);
        // has a return/throw at end
        $has_ending_statements = $final_actions === [ScopeChecker::ACTION_END];
        $has_leaving_statements = $has_ending_statements
            || (count($final_actions) && !in_array(ScopeChecker::ACTION_NONE, $final_actions, true));

        $has_break_statement = $final_actions === [ScopeChecker::ACTION_BREAK];
        $has_continue_statement = $final_actions === [ScopeChecker::ACTION_CONTINUE];

        $if_scope->final_actions = array_merge($final_actions, $if_scope->final_actions);

        // update the parent context as necessary
        $elseif_redefined_vars = $elseif_context->getRedefinedVars($original_context->vars_in_scope);

        if (!$has_leaving_statements) {
            if ($if_scope->new_vars === null) {
                $if_scope->new_vars = array_diff_key($elseif_context->vars_in_scope, $outer_context->vars_in_scope);
            } else {
                foreach ($if_scope->new_vars as $new_var => $type) {
                    if (!$elseif_context->hasVariable($new_var, $statements_checker)) {
                        unset($if_scope->new_vars[$new_var]);
                    } else {
                        $if_scope->new_vars[$new_var] = Type::combineUnionTypes(
                            $type,
                            $elseif_context->vars_in_scope[$new_var]
                        );
                    }
                }
            }

            $possibly_redefined_vars = $elseif_redefined_vars;

            foreach ($possibly_redefined_vars as $var_id => $_) {
                if (!isset($new_stmts_assigned_var_ids[$var_id])
                    && in_array($var_id, $changed_var_ids, true)
                ) {
                    unset($possibly_redefined_vars[$var_id]);
                }
            }

            $assigned_var_ids = array_merge($new_stmts_assigned_var_ids, $new_assigned_var_ids);

            if ($if_scope->assigned_var_ids === null) {
                $if_scope->assigned_var_ids = $assigned_var_ids;
            } else {
                $if_scope->assigned_var_ids = array_intersect_key($assigned_var_ids, $if_scope->assigned_var_ids);
            }

            if ($if_scope->redefined_vars === null) {
                $if_scope->redefined_vars = $elseif_redefined_vars;
                $if_scope->possibly_redefined_vars = $possibly_redefined_vars;
            } else {
                foreach ($if_scope->redefined_vars as $redefined_var => $type) {
                    if (!isset($elseif_redefined_vars[$redefined_var])) {
                        unset($if_scope->redefined_vars[$redefined_var]);
                    } else {
                        $if_scope->redefined_vars[$redefined_var] = Type::combineUnionTypes(
                            $elseif_redefined_vars[$redefined_var],
                            $type
                        );

                        if (isset($outer_context->vars_in_scope[$redefined_var])
                            && $if_scope->redefined_vars[$redefined_var]->equals(
                                $outer_context->vars_in_scope[$redefined_var]
                            )
                        ) {
                            unset($if_scope->redefined_vars[$redefined_var]);
                        }
                    }
                }

                foreach ($possibly_redefined_vars as $var => $type) {
                    if ($type->isMixed()) {
                        $if_scope->possibly_redefined_vars[$var] = $type;
                    } elseif (isset($if_scope->possibly_redefined_vars[$var])) {
                        $if_scope->possibly_redefined_vars[$var] = Type::combineUnionTypes(
                            $type,
                            $if_scope->possibly_redefined_vars[$var]
                        );
                    } else {
                        $if_scope->possibly_redefined_vars[$var] = $type;
                    }
                }
            }

            if ($if_scope->reasonable_clauses && $elseif_clauses) {
                $if_scope->reasonable_clauses = Algebra::combineOredClauses(
                    $if_scope->reasonable_clauses,
                    $elseif_clauses
                );
            } else {
                $if_scope->reasonable_clauses = [];
            }
        } else {
            $if_scope->reasonable_clauses = [];
        }

        if ($project_checker->infer_types_from_usage) {
            $elseif_possible_param_types = $elseif_context->possible_param_types;

            if ($if_scope->possible_param_types) {
                $vars_to_remove = [];

                foreach ($if_scope->possible_param_types as $var => $type) {
                    if (isset($elseif_possible_param_types[$var])) {
                        $if_scope->possible_param_types[$var] = Type::combineUnionTypes(
                            $elseif_possible_param_types[$var],
                            $type
                        );
                    } else {
                        $vars_to_remove[] = $var;
                    }
                }

                foreach ($vars_to_remove as $var) {
                    unset($if_scope->possible_param_types[$var]);
                }
            }
        }

        if ($negated_elseif_types) {
            if ($has_leaving_statements) {
                $changed_var_ids = [];

                $leaving_vars_reconciled = Reconciler::reconcileKeyedTypes(
                    $negated_elseif_types,
                    $pre_conditional_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_checker,
                    new CodeLocation($statements_checker->getSource(), $elseif, $outer_context->include_location),
                    $statements_checker->getSuppressedIssues()
                );

                $implied_outer_context = clone $elseif_context;
                $implied_outer_context->vars_in_scope = $leaving_vars_reconciled;

                $outer_context->update(
                    $elseif_context,
                    $implied_outer_context,
                    false,
                    array_keys($negated_elseif_types),
                    $if_scope->updated_vars
                );
            } else {
                $outer_context->update(
                    $old_elseif_context,
                    $elseif_context,
                    false,
                    array_keys($negated_elseif_types),
                    $if_scope->updated_vars
                );
            }
        }

        if (!$has_ending_statements) {
            $vars_possibly_in_scope = array_diff_key(
                $elseif_context->vars_possibly_in_scope,
                $outer_context->vars_possibly_in_scope
            );

            $possibly_assigned_var_ids = array_diff_key(
                $elseif_context->possibly_assigned_var_ids,
                $outer_context->possibly_assigned_var_ids
            );

            if ($has_leaving_statements && $loop_scope) {
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

                $loop_scope->vars_possibly_in_scope = array_merge(
                    $vars_possibly_in_scope,
                    $loop_scope->vars_possibly_in_scope
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

        if ($outer_context->collect_references) {
            $outer_context->referenced_var_ids = array_merge(
                $outer_context->referenced_var_ids,
                $elseif_context->referenced_var_ids
            );
        }

        $if_scope->negated_clauses = array_merge(
            $if_scope->negated_clauses,
            Algebra::negateFormula($elseif_clauses)
        );
    }

    /**
     * @param  StatementsChecker         $statements_checker
     * @param  PhpParser\Node\Stmt\Else_ $else
     * @param  IfScope                   $if_scope
     * @param  Context                   $else_context
     * @param  Context                   $outer_context
     *
     * @return false|null
     */
    protected static function analyzeElseBlock(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\Else_ $else,
        IfScope $if_scope,
        Context $else_context,
        Context $outer_context,
        LoopScope $loop_scope = null
    ) {
        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $original_context = clone $else_context;

        $else_context->clauses = Algebra::simplifyCNF(
            array_merge(
                $else_context->clauses,
                $if_scope->negated_clauses
            )
        );

        $else_types = Algebra::getTruthsFromFormula($else_context->clauses);

        if ($else_types) {
            $changed_var_ids = [];

            $else_vars_reconciled = Reconciler::reconcileKeyedTypes(
                $else_types,
                $else_context->vars_in_scope,
                $changed_var_ids,
                [],
                $statements_checker,
                new CodeLocation($statements_checker->getSource(), $else, $outer_context->include_location),
                $statements_checker->getSuppressedIssues()
            );

            $else_context->vars_in_scope = $else_vars_reconciled;

            $else_context->removeReconciledClauses($changed_var_ids);
        }

        $old_else_context = clone $else_context;

        $pre_stmts_assigned_var_ids = $else_context->assigned_var_ids;
        $else_context->assigned_var_ids = [];

        if ($statements_checker->analyze(
            $else->stmts,
            $else_context,
            $loop_scope
        ) === false
        ) {
            return false;
        }

        /** @var array<string, bool> */
        $new_assigned_var_ids = $else_context->assigned_var_ids;
        $else_context->assigned_var_ids = $pre_stmts_assigned_var_ids;

        if ($else_context->byref_constraints !== null) {
            $project_checker = $statements_checker->getFileChecker()->project_checker;

            foreach ($else_context->byref_constraints as $var_id => $byref_constraint) {
                if ($outer_context->byref_constraints !== null
                    && isset($outer_context->byref_constraints[$var_id])
                    && ($outer_constraint_type = $outer_context->byref_constraints[$var_id]->type)
                    && $byref_constraint->type
                    && !TypeChecker::isContainedBy(
                        $project_checker->codebase,
                        $byref_constraint->type,
                        $outer_constraint_type
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new ConflictingReferenceConstraint(
                            'There is more than one pass-by-reference constraint on ' . $var_id,
                            new CodeLocation($statements_checker, $else, $outer_context->include_location, true)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    $outer_context->byref_constraints[$var_id] = $byref_constraint;
                }
            }
        }

        if ($outer_context->collect_references) {
            $outer_context->referenced_var_ids = array_merge(
                $outer_context->referenced_var_ids,
                $else_context->referenced_var_ids
            );
        }

        $final_actions = ScopeChecker::getFinalControlActions($else->stmts);
        // has a return/throw at end
        $has_ending_statements = $final_actions === [ScopeChecker::ACTION_END];
        $has_leaving_statements = $has_ending_statements
            || (count($final_actions) && !in_array(ScopeChecker::ACTION_NONE, $final_actions, true));

        $has_break_statement = $final_actions === [ScopeChecker::ACTION_BREAK];
        $has_continue_statement = $final_actions === [ScopeChecker::ACTION_CONTINUE];

        $if_scope->final_actions = array_merge($final_actions, $if_scope->final_actions);

        $else_redefined_vars = $else_context->getRedefinedVars($original_context->vars_in_scope);

        // if it doesn't end in a return
        if (!$has_leaving_statements) {
            if ($if_scope->new_vars === null) {
                $if_scope->new_vars = array_diff_key($else_context->vars_in_scope, $outer_context->vars_in_scope);
            } else {
                foreach ($if_scope->new_vars as $new_var => $type) {
                    if (!$else_context->hasVariable($new_var)) {
                        unset($if_scope->new_vars[$new_var]);
                    } else {
                        $if_scope->new_vars[$new_var] = Type::combineUnionTypes(
                            $type,
                            $else_context->vars_in_scope[$new_var]
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
                            $type
                        );
                    }
                }

                foreach ($else_redefined_vars as $var => $type) {
                    if ($type->isMixed()) {
                        $if_scope->possibly_redefined_vars[$var] = $type;
                    } elseif (isset($if_scope->possibly_redefined_vars[$var])) {
                        $if_scope->possibly_redefined_vars[$var] = Type::combineUnionTypes(
                            $type,
                            $if_scope->possibly_redefined_vars[$var]
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

            $possibly_assigned_var_ids = array_diff_key(
                $else_context->possibly_assigned_var_ids,
                $outer_context->possibly_assigned_var_ids
            );

            if ($has_leaving_statements && $loop_scope) {
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

                $loop_scope->vars_possibly_in_scope = array_merge(
                    $vars_possibly_in_scope,
                    $loop_scope->vars_possibly_in_scope
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

        if ($project_checker->infer_types_from_usage) {
            $else_possible_param_types = $else_context->possible_param_types;

            if ($if_scope->possible_param_types) {
                $vars_to_remove = [];

                foreach ($if_scope->possible_param_types as $var => $type) {
                    if (isset($else_possible_param_types[$var])) {
                        $if_scope->possible_param_types[$var] = Type::combineUnionTypes(
                            $else_possible_param_types[$var],
                            $type
                        );
                    } else {
                        $vars_to_remove[] = $var;
                    }
                }

                foreach ($vars_to_remove as $var) {
                    unset($if_scope->possible_param_types[$var]);
                }
            }
        }
    }

    /**
     * Returns statements that are definitely evaluated before any statements after the end of the
     * if/elseif/else blocks
     *
     * @param  PhpParser\Node\Expr $stmt
     * @param  bool $inside_and
     *
     * @return PhpParser\Node\Expr|null
     */
    protected static function getDefinitelyEvaluatedExpression(PhpParser\Node\Expr $stmt)
    {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalXor
            ) {
                return self::getDefinitelyEvaluatedExpression($stmt->left);
            }

            return $stmt;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            return self::getDefinitelyEvaluatedExpression($stmt->expr);
        }

        return $stmt;
    }
}
