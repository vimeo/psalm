<?php
namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\AlgebraAnalyzer;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Internal\Clause;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ConflictingReferenceConstraint;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\RedundantConditionGivenDocblockType;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\Issue\RedundantCondition;
use Psalm\IssueBuffer;
use Psalm\Internal\Scope\IfScope;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Reconciler;
use function array_merge;
use function array_map;
use function array_diff_key;
use function array_filter;
use function array_values;
use function array_keys;
use function array_reduce;
use function array_combine;
use function preg_match;
use function preg_quote;
use function array_unique;
use function count;
use function in_array;
use function array_intersect;
use function strpos;
use function substr;
use function array_intersect_key;

/**
 * @internal
 */
class IfAnalyzer
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
     * @param  StatementsAnalyzer       $statements_analyzer
     * @param  PhpParser\Node\Stmt\If_ $stmt
     * @param  Context                 $context
     *
     * @return null|false
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\If_ $stmt,
        Context $context
    ) {
        $codebase = $statements_analyzer->getCodebase();

        $if_scope = new IfScope();

        try {
            $if_conditional_scope = self::analyzeIfConditional(
                $statements_analyzer,
                $stmt->cond,
                $context,
                $codebase,
                $if_scope,
                $context->branch_point ?: (int) $stmt->getAttribute('startFilePos')
            );

            $if_context = $if_conditional_scope->if_context;

            $original_context = $if_conditional_scope->original_context;
            $cond_referenced_var_ids = $if_conditional_scope->cond_referenced_var_ids;
            $cond_assigned_var_ids = $if_conditional_scope->cond_assigned_var_ids;
        } catch (\Psalm\Exception\ScopeAnalysisException $e) {
            return false;
        }

        $mixed_var_ids = [];

        foreach ($if_context->vars_in_scope as $var_id => $type) {
            if ($type->hasMixed() && isset($context->vars_in_scope[$var_id])) {
                $mixed_var_ids[] = $var_id;
            }
        }

        $if_clauses = Algebra::getFormula(
            \spl_object_id($stmt->cond),
            $stmt->cond,
            $context->self,
            $statements_analyzer,
            $codebase
        );

        if (count($if_clauses) > 200) {
            $if_clauses = [];
        }

        $if_clauses = array_values(
            array_map(
                /**
                 * @return Clause
                 */
                function (Clause $c) use ($mixed_var_ids) {
                    $keys = array_keys($c->possibilities);

                    $mixed_var_ids = \array_diff($mixed_var_ids, $keys);

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

        $entry_clauses = $context->clauses;

        // this will see whether any of the clauses in set A conflict with the clauses in set B
        AlgebraAnalyzer::checkForParadox(
            $context->clauses,
            $if_clauses,
            $statements_analyzer,
            $stmt->cond,
            $cond_assigned_var_ids
        );

        // if we have assignments in the if, we may have duplicate clauses
        if ($cond_assigned_var_ids) {
            $if_clauses = Algebra::simplifyCNF($if_clauses);
        }

        $if_context_clauses = array_merge($entry_clauses, $if_clauses);

        $if_context->clauses = Algebra::simplifyCNF($if_context_clauses);

        if ($if_context->reconciled_expression_clauses) {
            $reconciled_expression_clauses = $if_context->reconciled_expression_clauses;

            $if_context->clauses = array_values(
                array_filter(
                    $if_context->clauses,
                    function ($c) use ($reconciled_expression_clauses) {
                        return !in_array($c->getHash(), $reconciled_expression_clauses);
                    }
                )
            );

            if (count($if_context->clauses) === 1
                && $if_context->clauses[0]->wedge
                && !$if_context->clauses[0]->possibilities
            ) {
                $if_context->clauses = [];
                $if_context->reconciled_expression_clauses = [];
            }
        }

        // define this before we alter local claues after reconciliation
        $if_scope->reasonable_clauses = $if_context->clauses;

        try {
            $if_scope->negated_clauses = Algebra::negateFormula($if_clauses);
        } catch (\Psalm\Exception\ComplicatedExpressionException $e) {
            $if_scope->negated_clauses = [];
        }

        $if_scope->negated_types = Algebra::getTruthsFromFormula(
            Algebra::simplifyCNF(
                array_merge($context->clauses, $if_scope->negated_clauses)
            )
        );

        $active_if_types = [];

        $reconcilable_if_types = Algebra::getTruthsFromFormula(
            $if_context->clauses,
            \spl_object_id($stmt->cond),
            $cond_referenced_var_ids,
            $active_if_types
        );

        if (array_filter(
            $context->clauses,
            function ($clause) {
                return !!$clause->possibilities;
            }
        )) {
            $omit_keys = array_reduce(
                $context->clauses,
                /**
                 * @param array<string> $carry
                 * @return array<string>
                 */
                function ($carry, Clause $clause) {
                    return array_merge($carry, array_keys($clause->possibilities));
                },
                []
            );

            $omit_keys = array_combine($omit_keys, $omit_keys);
            $omit_keys = array_diff_key($omit_keys, Algebra::getTruthsFromFormula($context->clauses));

            $cond_referenced_var_ids = array_diff_key(
                $cond_referenced_var_ids,
                $omit_keys
            );
        }

        // if the if has an || in the conditional, we cannot easily reason about it
        if ($reconcilable_if_types) {
            $changed_var_ids = [];

            $if_vars_in_scope_reconciled =
                Reconciler::reconcileKeyedTypes(
                    $reconcilable_if_types,
                    $active_if_types,
                    $if_context->vars_in_scope,
                    $changed_var_ids,
                    $cond_referenced_var_ids,
                    $statements_analyzer,
                    $statements_analyzer->getTemplateTypeMap() ?: [],
                    $if_context->inside_loop,
                    $context->check_variables
                        ? new CodeLocation(
                            $statements_analyzer->getSource(),
                            $stmt->cond instanceof PhpParser\Node\Expr\BooleanNot
                                ? $stmt->cond->expr
                                : $stmt->cond,
                            $context->include_location
                        ) : null
                );

            $if_context->vars_in_scope = $if_vars_in_scope_reconciled;

            foreach ($reconcilable_if_types as $var_id => $_) {
                $if_context->vars_possibly_in_scope[$var_id] = true;
            }

            if ($changed_var_ids) {
                $if_context->clauses = Context::removeReconciledClauses($if_context->clauses, $changed_var_ids)[0];
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
                [],
                $temp_else_context->vars_in_scope,
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
                        $context->include_location
                    ) : null
            );

            $temp_else_context->vars_in_scope = $else_vars_reconciled;
        }

        // we calculate the vars redefined in a hypothetical else statement to determine
        // which vars of the if we can safely change
        $pre_assignment_else_redefined_vars = array_intersect_key(
            $temp_else_context->getRedefinedVars($context->vars_in_scope, true),
            $changed_var_ids
        );

        // this captures statements in the if conditional
        if ($codebase->find_unused_variables) {
            foreach ($if_context->unreferenced_vars as $var_id => $locations) {
                if (!isset($context->unreferenced_vars[$var_id])) {
                    if (isset($if_scope->new_unreferenced_vars[$var_id])) {
                        $if_scope->new_unreferenced_vars[$var_id] += $locations;
                    } else {
                        $if_scope->new_unreferenced_vars[$var_id] = $locations;
                    }
                } else {
                    $new_locations = array_diff_key(
                        $locations,
                        $context->unreferenced_vars[$var_id]
                    );

                    if ($new_locations) {
                        if (isset($if_scope->new_unreferenced_vars[$var_id])) {
                            $if_scope->new_unreferenced_vars[$var_id] += $locations;
                        } else {
                            $if_scope->new_unreferenced_vars[$var_id] = $locations;
                        }
                    }
                }
            }
        }

        // check the if
        if (self::analyzeIfBlock(
            $statements_analyzer,
            $stmt,
            $if_scope,
            $if_context,
            $old_if_context,
            $context,
            $pre_assignment_else_redefined_vars
        ) === false) {
            return false;
        }

        // check the else
        $else_context = clone $original_context;

        // check the elseifs
        foreach ($stmt->elseifs as $elseif) {
            if (self::analyzeElseIfBlock(
                $statements_analyzer,
                $elseif,
                $if_scope,
                $else_context,
                $context,
                $codebase,
                $else_context->branch_point ?: (int) $stmt->getAttribute('startFilePos')
            ) === false) {
                return false;
            }
        }

        if ($stmt->else) {
            if ($codebase->alter_code) {
                $else_context->branch_point =
                    $else_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
            }
        }

        if (self::analyzeElseBlock(
            $statements_analyzer,
            $stmt->else,
            $if_scope,
            $else_context,
            $context
        ) === false) {
            return false;
        }

        if ($context->loop_scope) {
            $context->loop_scope->final_actions = array_unique(
                array_merge(
                    $context->loop_scope->final_actions,
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
                        $statements_analyzer
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

        if ($if_scope->possibly_redefined_vars) {
            foreach ($if_scope->possibly_redefined_vars as $var_id => $type) {
                if (isset($context->vars_in_scope[$var_id])
                    && !$type->failed_reconciliation
                    && !isset($if_scope->updated_vars[$var_id])
                ) {
                    $combined_type = Type::combineUnionTypes(
                        $context->vars_in_scope[$var_id],
                        $type,
                        $codebase
                    );

                    if ($combined_type->equals($context->vars_in_scope[$var_id])) {
                        continue;
                    }

                    $context->removeDescendents($var_id, $combined_type);
                    $context->vars_in_scope[$var_id] = $combined_type;
                }
            }
        }

        if ($codebase->find_unused_variables) {
            foreach ($if_scope->new_unreferenced_vars as $var_id => $locations) {
                if (($stmt->else
                        && (isset($if_scope->assigned_var_ids[$var_id]) || isset($if_scope->new_vars[$var_id])))
                    || !isset($context->vars_in_scope[$var_id])
                ) {
                    $context->unreferenced_vars[$var_id] = $locations;
                } elseif (isset($if_scope->possibly_assigned_var_ids[$var_id])
                    || isset($if_context->possibly_assigned_var_ids[$var_id])
                ) {
                    if (!isset($context->unreferenced_vars[$var_id])) {
                        $context->unreferenced_vars[$var_id] = $locations;
                    } else {
                        $context->unreferenced_vars[$var_id] += $locations;
                    }
                } else {
                    $statements_analyzer->registerVariableUses($locations);
                }
            }

            $context->possibly_assigned_var_ids += $if_scope->possibly_assigned_var_ids;
        }

        if (!in_array(ScopeAnalyzer::ACTION_NONE, $if_scope->final_actions, true)) {
            $context->has_returned = true;
        }

        return null;
    }

    /**
     * @return \Psalm\Internal\Scope\IfConditionalScope
     */
    public static function analyzeIfConditional(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $cond,
        Context $outer_context,
        Codebase $codebase,
        IfScope $if_scope,
        ?int $branch_point
    ) {
        $entry_clauses = [];

        // used when evaluating elseifs
        if ($if_scope->negated_clauses) {
            $entry_clauses = array_merge($outer_context->clauses, $if_scope->negated_clauses);

            $changed_var_ids = [];

            if ($if_scope->negated_types) {
                $vars_reconciled = Reconciler::reconcileKeyedTypes(
                    $if_scope->negated_types,
                    [],
                    $outer_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    [],
                    $outer_context->inside_loop,
                    new CodeLocation(
                        $statements_analyzer->getSource(),
                        $cond instanceof PhpParser\Node\Expr\BooleanNot
                            ? $cond->expr
                            : $cond,
                        $outer_context->include_location,
                        false
                    )
                );

                if ($changed_var_ids) {
                    $outer_context = clone $outer_context;
                    $outer_context->vars_in_scope = $vars_reconciled;

                    $entry_clauses = array_values(
                        array_filter(
                            $entry_clauses,
                            /** @return bool */
                            function (Clause $c) use ($changed_var_ids) {
                                return count($c->possibilities) > 1
                                    || $c->wedge
                                    || !isset($changed_var_ids[array_keys($c->possibilities)[0]]);
                            }
                        )
                    );
                }
            }
        }

        // get the first expression in the if, which should be evaluated on its own
        // this allows us to update the context of $matches in
        // if (!preg_match('/a/', 'aa', $matches)) {
        //   exit
        // }
        // echo $matches[0];
        $externally_applied_if_cond_expr = self::getDefinitelyEvaluatedExpressionAfterIf($cond);

        $internally_applied_if_cond_expr = self::getDefinitelyEvaluatedExpressionInsideIf($cond);

        $was_inside_conditional = $outer_context->inside_conditional;

        $outer_context->inside_conditional = true;

        $pre_condition_vars_in_scope = $outer_context->vars_in_scope;

        $referenced_var_ids = $outer_context->referenced_var_ids;
        $outer_context->referenced_var_ids = [];

        $pre_assigned_var_ids = $outer_context->assigned_var_ids;
        $outer_context->assigned_var_ids = [];

        $if_context = null;

        if ($internally_applied_if_cond_expr !== $externally_applied_if_cond_expr) {
            $if_context = clone $outer_context;
        }

        if ($externally_applied_if_cond_expr) {
            if (ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $externally_applied_if_cond_expr,
                $outer_context
            ) === false) {
                throw new \Psalm\Exception\ScopeAnalysisException();
            }
        }

        $first_cond_assigned_var_ids = $outer_context->assigned_var_ids;
        $outer_context->assigned_var_ids = array_merge(
            $pre_assigned_var_ids,
            $first_cond_assigned_var_ids
        );

        $first_cond_referenced_var_ids = $outer_context->referenced_var_ids;
        $outer_context->referenced_var_ids = array_merge(
            $referenced_var_ids,
            $first_cond_referenced_var_ids
        );

        if (!$was_inside_conditional) {
            $outer_context->inside_conditional = false;
        }

        if (!$if_context) {
            $if_context = clone $outer_context;
        }

        $if_conditional_context = clone $if_context;
        $if_conditional_context->if_context = $if_context;
        $if_conditional_context->if_scope = $if_scope;

        if ($codebase->alter_code) {
            $if_context->branch_point = $branch_point;
        }

        // we need to clone the current context so our ongoing updates
        // to $outer_context don't mess with elseif/else blocks
        $original_context = clone $outer_context;

        if ($internally_applied_if_cond_expr !== $cond
            || $externally_applied_if_cond_expr !== $cond
        ) {
            $assigned_var_ids = $outer_context->assigned_var_ids;
            $if_conditional_context->assigned_var_ids = [];

            $referenced_var_ids = $outer_context->referenced_var_ids;
            $if_conditional_context->referenced_var_ids = [];

            $if_conditional_context->inside_conditional = true;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $cond, $if_conditional_context) === false) {
                throw new \Psalm\Exception\ScopeAnalysisException();
            }

            $if_conditional_context->inside_conditional = false;

            /** @var array<string, bool> */
            $more_cond_referenced_var_ids = $if_conditional_context->referenced_var_ids;
            $if_conditional_context->referenced_var_ids = array_merge(
                $more_cond_referenced_var_ids,
                $referenced_var_ids
            );

            $cond_referenced_var_ids = array_merge(
                $first_cond_referenced_var_ids,
                $more_cond_referenced_var_ids
            );

            /** @var array<string, bool> */
            $more_cond_assigned_var_ids = $if_conditional_context->assigned_var_ids;
            $if_conditional_context->assigned_var_ids = array_merge(
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
                $if_conditional_context->vars_in_scope,
                $pre_condition_vars_in_scope,
                $cond_referenced_var_ids,
                $cond_assigned_var_ids
            )
        );

        $cond_type = $statements_analyzer->node_data->getType($cond);

        if ($cond_type !== null) {
            if ($cond_type->isFalse()) {
                if ($cond_type->from_docblock) {
                    if (IssueBuffer::accepts(
                        new DocblockTypeContradiction(
                            'if (false) is impossible',
                            new CodeLocation($statements_analyzer, $cond)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new TypeDoesNotContainType(
                            'if (false) is impossible',
                            new CodeLocation($statements_analyzer, $cond)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            } elseif ($cond_type->isTrue()) {
                if ($cond_type->from_docblock) {
                    if (IssueBuffer::accepts(
                        new RedundantConditionGivenDocblockType(
                            'if (true) is redundant',
                            new CodeLocation($statements_analyzer, $cond)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new RedundantCondition(
                            'if (true) is redundant',
                            new CodeLocation($statements_analyzer, $cond)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        // get all the var ids that were referened in the conditional, but not assigned in it
        $cond_referenced_var_ids = array_diff_key($cond_referenced_var_ids, $cond_assigned_var_ids);

        $cond_referenced_var_ids = array_merge($newish_var_ids, $cond_referenced_var_ids);

        return new \Psalm\Internal\Scope\IfConditionalScope(
            $if_context,
            $original_context,
            $cond_referenced_var_ids,
            $cond_assigned_var_ids,
            $entry_clauses
        );
    }

    /**
     * @param  StatementsAnalyzer        $statements_analyzer
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
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\If_ $stmt,
        IfScope $if_scope,
        Context $if_context,
        Context $old_if_context,
        Context $outer_context,
        array $pre_assignment_else_redefined_vars
    ) {
        $codebase = $statements_analyzer->getCodebase();

        $if_context->parent_context = $outer_context;

        $assigned_var_ids = $if_context->assigned_var_ids;
        $possibly_assigned_var_ids = $if_context->possibly_assigned_var_ids;
        $if_context->assigned_var_ids = [];
        $if_context->possibly_assigned_var_ids = [];

        if ($statements_analyzer->analyze(
            $stmt->stmts,
            $if_context
        ) === false
        ) {
            return false;
        }

        $final_actions = ScopeAnalyzer::getFinalControlActions(
            $stmt->stmts,
            $statements_analyzer->node_data,
            $codebase->config->exit_functions,
            $outer_context->break_types
        );

        $has_ending_statements = $final_actions === [ScopeAnalyzer::ACTION_END];

        $has_leaving_statements = $has_ending_statements
            || (count($final_actions) && !in_array(ScopeAnalyzer::ACTION_NONE, $final_actions, true));

        $has_break_statement = $final_actions === [ScopeAnalyzer::ACTION_BREAK];
        $has_continue_statement = $final_actions === [ScopeAnalyzer::ACTION_CONTINUE];
        $has_leave_switch_statement = $final_actions === [ScopeAnalyzer::ACTION_LEAVE_SWITCH];

        $if_scope->final_actions = $final_actions;

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
                    && !TypeAnalyzer::isContainedBy(
                        $codebase,
                        $byref_constraint->type,
                        $outer_constraint_type
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new ConflictingReferenceConstraint(
                            'There is more than one pass-by-reference constraint on ' . $var_id,
                            new CodeLocation($statements_analyzer, $stmt, $outer_context->include_location, true)
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

        if ($codebase->find_unused_variables) {
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

            $changed_var_ids = $new_assigned_var_ids;

            // if the variable was only set in the conditional, it's not possibly redefined
            foreach ($if_scope->possibly_redefined_vars as $var_id => $_) {
                if (!isset($new_possibly_assigned_var_ids[$var_id])
                    && isset($if_scope->if_cond_changed_var_ids[$var_id])
                ) {
                    unset($if_scope->possibly_redefined_vars[$var_id]);
                }
            }

            if ($if_scope->reasonable_clauses) {
                // remove all reasonable clauses that would be negated by the if stmts
                foreach ($changed_var_ids as $var_id => $_) {
                    $if_scope->reasonable_clauses = Context::filterClauses(
                        $var_id,
                        $if_scope->reasonable_clauses,
                        isset($if_context->vars_in_scope[$var_id]) ? $if_context->vars_in_scope[$var_id] : null,
                        $statements_analyzer
                    );
                }
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
                    [],
                    $outer_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    $statements_analyzer->getTemplateTypeMap() ?: [],
                    $outer_context->inside_loop,
                    new CodeLocation(
                        $statements_analyzer->getSource(),
                        $stmt->cond instanceof PhpParser\Node\Expr\BooleanNot
                            ? $stmt->cond->expr
                            : $stmt->cond,
                        $outer_context->include_location,
                        false
                    )
                );

                foreach ($changed_var_ids as $changed_var_id => $_) {
                    $outer_context->removeVarFromConflictingClauses($changed_var_id);
                }

                $changed_var_ids += $new_assigned_var_ids;

                foreach ($changed_var_ids as $var_id => $_) {
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
                            $type,
                            $codebase
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

            if ($if_context->loop_scope) {
                if (!$has_continue_statement && !$has_break_statement) {
                    $if_scope->new_vars_possibly_in_scope = $vars_possibly_in_scope;
                }

                $if_context->loop_scope->vars_possibly_in_scope = array_merge(
                    $vars_possibly_in_scope,
                    $if_context->loop_scope->vars_possibly_in_scope
                );
            } elseif (!$has_leaving_statements) {
                $if_scope->new_vars_possibly_in_scope = $vars_possibly_in_scope;
            }

            if ($codebase->find_unused_variables && (!$has_leaving_statements || $has_leave_switch_statement)) {
                foreach ($if_context->unreferenced_vars as $var_id => $locations) {
                    if (!isset($outer_context->unreferenced_vars[$var_id])) {
                        if (isset($if_scope->new_unreferenced_vars[$var_id])) {
                            $if_scope->new_unreferenced_vars[$var_id] += $locations;
                        } else {
                            $if_scope->new_unreferenced_vars[$var_id] = $locations;
                        }
                    } else {
                        $new_locations = array_diff_key(
                            $locations,
                            $outer_context->unreferenced_vars[$var_id]
                        );

                        if ($new_locations) {
                            if (isset($if_scope->new_unreferenced_vars[$var_id])) {
                                $if_scope->new_unreferenced_vars[$var_id] += $locations;
                            } else {
                                $if_scope->new_unreferenced_vars[$var_id] = $locations;
                            }
                        }
                    }
                }
            }
        }

        if ($outer_context->collect_exceptions) {
            $outer_context->mergeExceptions($if_context);
        }
    }

    /**
     * @param  StatementsAnalyzer           $statements_analyzer
     * @param  PhpParser\Node\Stmt\ElseIf_ $elseif
     * @param  IfScope                     $if_scope
     * @param  Context                     $elseif_context
     * @param  Context                     $outer_context
     *
     * @return false|null
     */
    protected static function analyzeElseIfBlock(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\ElseIf_ $elseif,
        IfScope $if_scope,
        Context $else_context,
        Context $outer_context,
        Codebase $codebase,
        ?int $branch_point
    ) {
        $pre_conditional_context = clone $else_context;

        try {
            $if_conditional_scope = self::analyzeIfConditional(
                $statements_analyzer,
                $elseif->cond,
                $else_context,
                $codebase,
                $if_scope,
                $branch_point
            );

            $elseif_context = $if_conditional_scope->if_context;
            $cond_referenced_var_ids = $if_conditional_scope->cond_referenced_var_ids;
            $cond_assigned_var_ids = $if_conditional_scope->cond_assigned_var_ids;
            $entry_clauses = $if_conditional_scope->entry_clauses;
        } catch (\Psalm\Exception\ScopeAnalysisException $e) {
            return false;
        }

        $mixed_var_ids = [];

        foreach ($elseif_context->vars_in_scope as $var_id => $type) {
            if ($type->hasMixed()) {
                $mixed_var_ids[] = $var_id;
            }
        }

        $elseif_clauses = Algebra::getFormula(
            \spl_object_id($elseif->cond),
            $elseif->cond,
            $else_context->self,
            $statements_analyzer,
            $codebase
        );

        $elseif_clauses = array_map(
            /**
             * @return Clause
             */
            function (Clause $c) use ($mixed_var_ids) {
                $keys = array_keys($c->possibilities);

                $mixed_var_ids = \array_diff($mixed_var_ids, $keys);

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
            function (Clause $c) use ($cond_assigned_var_ids) {
                $keys = array_keys($c->possibilities);

                foreach ($keys as $key) {
                    foreach ($cond_assigned_var_ids as $conditional_assigned_var_id => $_) {
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
        AlgebraAnalyzer::checkForParadox(
            $entry_clauses,
            $elseif_clauses,
            $statements_analyzer,
            $elseif->cond,
            $cond_assigned_var_ids
        );

        $elseif_context_clauses = array_merge($entry_clauses, $elseif_clauses);

        if ($elseif_context->reconciled_expression_clauses) {
            $reconciled_expression_clauses = $elseif_context->reconciled_expression_clauses;

            $elseif_context_clauses = array_values(
                array_filter(
                    $elseif_context_clauses,
                    function ($c) use ($reconciled_expression_clauses) {
                        return !in_array($c->getHash(), $reconciled_expression_clauses);
                    }
                )
            );
        }

        $elseif_context->clauses = Algebra::simplifyCNF($elseif_context_clauses);

        $active_elseif_types = [];

        try {
            if (array_filter(
                $entry_clauses,
                function ($clause) {
                    return !!$clause->possibilities;
                }
            )) {
                $omit_keys = array_reduce(
                    $entry_clauses,
                    /**
                     * @param array<string> $carry
                     * @return array<string>
                     */
                    function ($carry, Clause $clause) {
                        return array_merge($carry, array_keys($clause->possibilities));
                    },
                    []
                );

                $omit_keys = array_combine($omit_keys, $omit_keys);
                $omit_keys = array_diff_key($omit_keys, Algebra::getTruthsFromFormula($entry_clauses));

                $cond_referenced_var_ids = array_diff_key(
                    $cond_referenced_var_ids,
                    $omit_keys
                );
            }
            $reconcilable_elseif_types = Algebra::getTruthsFromFormula(
                $elseif_context->clauses,
                \spl_object_id($elseif->cond),
                $cond_referenced_var_ids,
                $active_elseif_types
            );
            $negated_elseif_types = Algebra::getTruthsFromFormula(
                Algebra::negateFormula($elseif_clauses)
            );
        } catch (\Psalm\Exception\ComplicatedExpressionException $e) {
            $reconcilable_elseif_types = [];
            $negated_elseif_types = [];
        }

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

        $changed_var_ids = [];

        // if the elseif has an || in the conditional, we cannot easily reason about it
        if ($reconcilable_elseif_types) {
            $elseif_vars_reconciled = Reconciler::reconcileKeyedTypes(
                $reconcilable_elseif_types,
                $active_elseif_types,
                $elseif_context->vars_in_scope,
                $changed_var_ids,
                $cond_referenced_var_ids,
                $statements_analyzer,
                $statements_analyzer->getTemplateTypeMap() ?: [],
                $elseif_context->inside_loop,
                new CodeLocation(
                    $statements_analyzer->getSource(),
                    $elseif->cond instanceof PhpParser\Node\Expr\BooleanNot
                        ? $elseif->cond->expr
                        : $elseif->cond,
                    $outer_context->include_location
                )
            );

            $elseif_context->vars_in_scope = $elseif_vars_reconciled;

            if ($changed_var_ids) {
                $elseif_context->clauses = Context::removeReconciledClauses(
                    $elseif_context->clauses,
                    $changed_var_ids
                )[0];
            }
        }

        $pre_stmts_assigned_var_ids = $elseif_context->assigned_var_ids;
        $elseif_context->assigned_var_ids = [];
        $pre_stmts_possibly_assigned_var_ids = $elseif_context->possibly_assigned_var_ids;
        $elseif_context->possibly_assigned_var_ids = [];

        if ($statements_analyzer->analyze(
            $elseif->stmts,
            $elseif_context
        ) === false
        ) {
            return false;
        }

        /** @var array<string, bool> */
        $new_stmts_assigned_var_ids = $elseif_context->assigned_var_ids;
        $elseif_context->assigned_var_ids = $pre_stmts_assigned_var_ids + $new_stmts_assigned_var_ids;

        /** @var array<string, bool> */
        $new_stmts_possibly_assigned_var_ids = $elseif_context->possibly_assigned_var_ids;
        $elseif_context->possibly_assigned_var_ids =
            $pre_stmts_possibly_assigned_var_ids + $new_stmts_possibly_assigned_var_ids;

        if ($elseif_context->byref_constraints !== null) {
            foreach ($elseif_context->byref_constraints as $var_id => $byref_constraint) {
                if ($outer_context->byref_constraints !== null
                    && isset($outer_context->byref_constraints[$var_id])
                    && ($outer_constraint_type = $outer_context->byref_constraints[$var_id]->type)
                    && $byref_constraint->type
                    && !TypeAnalyzer::isContainedBy(
                        $codebase,
                        $byref_constraint->type,
                        $outer_constraint_type
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new ConflictingReferenceConstraint(
                            'There is more than one pass-by-reference constraint on ' . $var_id,
                            new CodeLocation($statements_analyzer, $elseif, $outer_context->include_location, true)
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

        $final_actions = ScopeAnalyzer::getFinalControlActions(
            $elseif->stmts,
            $statements_analyzer->node_data,
            $codebase->config->exit_functions,
            $outer_context->break_types
        );
        // has a return/throw at end
        $has_ending_statements = $final_actions === [ScopeAnalyzer::ACTION_END];
        $has_leaving_statements = $has_ending_statements
            || (count($final_actions) && !in_array(ScopeAnalyzer::ACTION_NONE, $final_actions, true));

        $has_break_statement = $final_actions === [ScopeAnalyzer::ACTION_BREAK];
        $has_continue_statement = $final_actions === [ScopeAnalyzer::ACTION_CONTINUE];
        $has_leave_switch_statement = $final_actions === [ScopeAnalyzer::ACTION_LEAVE_SWITCH];

        $if_scope->final_actions = array_merge($final_actions, $if_scope->final_actions);

        // update the parent context as necessary
        $elseif_redefined_vars = $elseif_context->getRedefinedVars($outer_context->vars_in_scope);

        if (!$has_leaving_statements) {
            if ($if_scope->new_vars === null) {
                $if_scope->new_vars = array_diff_key($elseif_context->vars_in_scope, $outer_context->vars_in_scope);
            } else {
                foreach ($if_scope->new_vars as $new_var => $type) {
                    if (!$elseif_context->hasVariable($new_var, $statements_analyzer)) {
                        unset($if_scope->new_vars[$new_var]);
                    } else {
                        $if_scope->new_vars[$new_var] = Type::combineUnionTypes(
                            $type,
                            $elseif_context->vars_in_scope[$new_var],
                            $codebase
                        );
                    }
                }
            }

            $possibly_redefined_vars = $elseif_redefined_vars;

            foreach ($possibly_redefined_vars as $var_id => $_) {
                if (!isset($new_stmts_assigned_var_ids[$var_id])
                    && isset($changed_var_ids[$var_id])
                ) {
                    unset($possibly_redefined_vars[$var_id]);
                }
            }

            $assigned_var_ids = array_merge($new_stmts_assigned_var_ids, $cond_assigned_var_ids);

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
                            $type,
                            $codebase
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

            $reasonable_clause_count = count($if_scope->reasonable_clauses);

            if ($reasonable_clause_count && $reasonable_clause_count < 20000 && $elseif_clauses) {
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

        if ($negated_elseif_types) {
            if ($has_leaving_statements) {
                $changed_var_ids = [];

                $leaving_vars_reconciled = Reconciler::reconcileKeyedTypes(
                    $negated_elseif_types,
                    [],
                    $pre_conditional_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    $statements_analyzer->getTemplateTypeMap() ?: [],
                    $elseif_context->inside_loop,
                    new CodeLocation($statements_analyzer->getSource(), $elseif, $outer_context->include_location)
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
            }
        }

        if (!$has_ending_statements) {
            $vars_possibly_in_scope = array_diff_key(
                $elseif_context->vars_possibly_in_scope,
                $outer_context->vars_possibly_in_scope
            );

            $possibly_assigned_var_ids = $new_stmts_possibly_assigned_var_ids;

            if ($has_leaving_statements && $elseif_context->loop_scope) {
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

                $elseif_context->loop_scope->vars_possibly_in_scope = array_merge(
                    $vars_possibly_in_scope,
                    $elseif_context->loop_scope->vars_possibly_in_scope
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

            if ($codebase->find_unused_variables &&  (!$has_leaving_statements || $has_leave_switch_statement)) {
                foreach ($elseif_context->unreferenced_vars as $var_id => $locations) {
                    if (!isset($outer_context->unreferenced_vars[$var_id])) {
                        if (isset($if_scope->new_unreferenced_vars[$var_id])) {
                            $if_scope->new_unreferenced_vars[$var_id] += $locations;
                        } else {
                            $if_scope->new_unreferenced_vars[$var_id] = $locations;
                        }
                    } else {
                        $new_locations = array_diff_key(
                            $locations,
                            $outer_context->unreferenced_vars[$var_id]
                        );

                        if ($new_locations) {
                            if (isset($if_scope->new_unreferenced_vars[$var_id])) {
                                $if_scope->new_unreferenced_vars[$var_id] += $locations;
                            } else {
                                $if_scope->new_unreferenced_vars[$var_id] = $locations;
                            }
                        }
                    }
                }
            }
        }

        if ($codebase->find_unused_variables) {
            $outer_context->referenced_var_ids = array_merge(
                $outer_context->referenced_var_ids,
                $elseif_context->referenced_var_ids
            );
        }

        if ($outer_context->collect_exceptions) {
            $outer_context->mergeExceptions($elseif_context);
        }

        try {
            $if_scope->negated_clauses = Algebra::simplifyCNF(
                array_merge(
                    $if_scope->negated_clauses,
                    Algebra::negateFormula($elseif_clauses)
                )
            );
        } catch (\Psalm\Exception\ComplicatedExpressionException $e) {
            $if_scope->negated_clauses = [];
        }
    }

    /**
     * @param  StatementsAnalyzer         $statements_analyzer
     * @param  PhpParser\Node\Stmt\Else_|null $else
     * @param  IfScope                   $if_scope
     * @param  Context                   $else_context
     * @param  Context                   $outer_context
     *
     * @return false|null
     */
    protected static function analyzeElseBlock(
        StatementsAnalyzer $statements_analyzer,
        $else,
        IfScope $if_scope,
        Context $else_context,
        Context $outer_context
    ) {
        $codebase = $statements_analyzer->getCodebase();

        if (!$else && !$if_scope->negated_clauses && !$else_context->clauses) {
            $if_scope->final_actions = array_merge([ScopeAnalyzer::ACTION_NONE], $if_scope->final_actions);
            $if_scope->assigned_var_ids = [];
            $if_scope->new_vars = [];
            $if_scope->redefined_vars = [];
            $if_scope->reasonable_clauses = [];

            return;
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

            return;
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

        /** @var array<string, bool> */
        $new_assigned_var_ids = $else_context->assigned_var_ids;
        $else_context->assigned_var_ids = $pre_stmts_assigned_var_ids;

        /** @var array<string, bool> */
        $new_possibly_assigned_var_ids = $else_context->possibly_assigned_var_ids;
        $else_context->possibly_assigned_var_ids = $pre_possibly_assigned_var_ids + $new_possibly_assigned_var_ids;

        if ($else && $else_context->byref_constraints !== null) {
            foreach ($else_context->byref_constraints as $var_id => $byref_constraint) {
                if ($outer_context->byref_constraints !== null
                    && isset($outer_context->byref_constraints[$var_id])
                    && ($outer_constraint_type = $outer_context->byref_constraints[$var_id]->type)
                    && $byref_constraint->type
                    && !TypeAnalyzer::isContainedBy(
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

        if ($else && $codebase->find_unused_variables) {
            $outer_context->referenced_var_ids = array_merge(
                $outer_context->referenced_var_ids,
                $else_context->referenced_var_ids
            );
        }

        $final_actions = $else
            ? ScopeAnalyzer::getFinalControlActions(
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
        $has_leave_switch_statement = $final_actions === [ScopeAnalyzer::ACTION_LEAVE_SWITCH];

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

            if ($codebase->find_unused_variables && (!$has_leaving_statements || $has_leave_switch_statement)) {
                foreach ($else_context->unreferenced_vars as $var_id => $locations) {
                    if (!isset($outer_context->unreferenced_vars[$var_id])) {
                        if (isset($if_scope->new_unreferenced_vars[$var_id])) {
                            $if_scope->new_unreferenced_vars[$var_id] += $locations;
                        } else {
                            $if_scope->new_unreferenced_vars[$var_id] = $locations;
                        }
                    } else {
                        $new_locations = array_diff_key(
                            $locations,
                            $outer_context->unreferenced_vars[$var_id]
                        );

                        if ($new_locations) {
                            if (isset($if_scope->new_unreferenced_vars[$var_id])) {
                                $if_scope->new_unreferenced_vars[$var_id] += $locations;
                            } else {
                                $if_scope->new_unreferenced_vars[$var_id] = $locations;
                            }
                        }
                    }
                }
            }
        }

        if ($outer_context->collect_exceptions) {
            $outer_context->mergeExceptions($else_context);
        }
    }

    /**
     * Returns statements that are definitely evaluated before any statements after the end of the
     * if/elseif/else blocks
     *
     * @param  PhpParser\Node\Expr $stmt
     *
     * @return PhpParser\Node\Expr|null
     */
    private static function getDefinitelyEvaluatedExpressionAfterIf(PhpParser\Node\Expr $stmt)
    {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalXor
            ) {
                return self::getDefinitelyEvaluatedExpressionAfterIf($stmt->left);
            }

            return $stmt;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            $inner_stmt = self::getDefinitelyEvaluatedExpressionInsideIf($stmt->expr);

            if ($inner_stmt !== $stmt->expr) {
                return $inner_stmt;
            }
        }

        return $stmt;
    }

    /**
     * Returns statements that are definitely evaluated before any statements inside
     * the if block
     *
     * @param  PhpParser\Node\Expr $stmt
     *
     * @return PhpParser\Node\Expr|null
     */
    private static function getDefinitelyEvaluatedExpressionInsideIf(PhpParser\Node\Expr $stmt)
    {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalXor
            ) {
                return self::getDefinitelyEvaluatedExpressionInsideIf($stmt->left);
            }

            return $stmt;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            $inner_stmt = self::getDefinitelyEvaluatedExpressionAfterIf($stmt->expr);

            if ($inner_stmt !== $stmt->expr) {
                return $inner_stmt;
            }
        }

        return $stmt;
    }
}
