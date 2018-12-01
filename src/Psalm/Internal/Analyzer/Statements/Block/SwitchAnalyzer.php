<?php
namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\AlgebraAnalyzer;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ContinueOutsideLoop;
use Psalm\Issue\ParadoxicalCondition;
use Psalm\IssueBuffer;
use Psalm\Internal\Scope\CaseScope;
use Psalm\Internal\Scope\SwitchScope;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Reconciler;

/**
 * @internal
 */
class SwitchAnalyzer
{
    /**
     * @param   StatementsAnalyzer               $statements_analyzer
     * @param   PhpParser\Node\Stmt\Switch_     $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Switch_ $stmt,
        Context $context
    ) {
        $codebase = $statements_analyzer->getCodebase();

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->cond, $context) === false) {
            return false;
        }

        $switch_var_id = ExpressionAnalyzer::getArrayVarId(
            $stmt->cond,
            null,
            $statements_analyzer
        );

        $original_context = clone $context;

        // the last statement always breaks, by default
        $last_case_exit_type = 'break';

        $case_exit_types = new \SplFixedArray(count($stmt->cases));

        $has_default = false;

        $case_action_map = [];

        $config = \Psalm\Config::getInstance();

        // create a map of case statement -> ultimate exit type
        for ($i = count($stmt->cases) - 1; $i >= 0; --$i) {
            $case = $stmt->cases[$i];

            $case_actions = $case_action_map[$i] = ScopeAnalyzer::getFinalControlActions(
                $case->stmts,
                $config->exit_functions,
                true
            );

            if (!in_array(ScopeAnalyzer::ACTION_NONE, $case_actions, true)) {
                if ($case_actions === [ScopeAnalyzer::ACTION_END]) {
                    $last_case_exit_type = 'return_throw';
                } elseif ($case_actions === [ScopeAnalyzer::ACTION_CONTINUE]) {
                    $last_case_exit_type = 'continue';
                } elseif (in_array(ScopeAnalyzer::ACTION_LEAVE_SWITCH, $case_actions, true)) {
                    $last_case_exit_type = 'break';
                }
            }

            $case_exit_types[$i] = $last_case_exit_type;
        }

        $switch_scope = new SwitchScope();

        for ($i = 0, $l = count($stmt->cases); $i < $l; $i++) {
            $case = $stmt->cases[$i];

            /** @var string */
            $case_exit_type = $case_exit_types[$i];

            $case_actions = $case_action_map[$i];

            if (!$case->cond) {
                $has_default = true;
            }

            if (self::handleCase(
                $statements_analyzer,
                $codebase,
                $stmt,
                $switch_var_id,
                $case,
                $context,
                $original_context,
                $case_exit_type,
                $case_actions,
                $i === $l - 1,
                $switch_scope
            ) === false
            ) {
                return false;
            }
        }

        $all_options_matched = $has_default;

        if (!$has_default && $switch_scope->negated_clauses && $switch_var_id) {
            $entry_clauses = Algebra::simplifyCNF(
                array_merge(
                    $original_context->clauses,
                    $switch_scope->negated_clauses
                )
            );

            $reconcilable_if_types = Algebra::getTruthsFromFormula($entry_clauses);

            // if the if has an || in the conditional, we cannot easily reason about it
            if ($reconcilable_if_types && isset($reconcilable_if_types[$switch_var_id])) {
                $changed_var_ids = [];

                $case_vars_in_scope_reconciled =
                    Reconciler::reconcileKeyedTypes(
                        $reconcilable_if_types,
                        $original_context->vars_in_scope,
                        $changed_var_ids,
                        [],
                        $statements_analyzer
                    );

                if (isset($case_vars_in_scope_reconciled[$switch_var_id])
                    && $case_vars_in_scope_reconciled[$switch_var_id]->isEmpty()
                ) {
                    $all_options_matched = true;
                }
            }
        }

        // only update vars if there is a default or all possible cases accounted for
        // if the default has a throw/return/continue, that should be handled above
        if ($all_options_matched) {
            if ($switch_scope->new_vars_in_scope) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $switch_scope->new_vars_in_scope);
            }

            if ($switch_scope->redefined_vars) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $switch_scope->redefined_vars);
            }

            if ($switch_scope->possibly_redefined_vars) {
                foreach ($switch_scope->possibly_redefined_vars as $var_id => $type) {
                    if (!isset($switch_scope->redefined_vars[$var_id])
                        && !isset($switch_scope->new_vars_in_scope[$var_id])
                    ) {
                        $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $type,
                            $context->vars_in_scope[$var_id]
                        );
                    }
                }
            }

            /** @psalm-suppress UndefinedPropertyAssignment */
            $stmt->allMatched = true;
        } elseif ($switch_scope->possibly_redefined_vars) {
            foreach ($switch_scope->possibly_redefined_vars as $var_id => $type) {
                $context->vars_in_scope[$var_id] = Type::combineUnionTypes($type, $context->vars_in_scope[$var_id]);
            }
        }

        if ($switch_scope->new_assigned_var_ids) {
            $context->assigned_var_ids += $switch_scope->new_assigned_var_ids;
        }

        if ($context->collect_references) {
            foreach ($switch_scope->new_unreferenced_vars as $var_id => $locations) {
                if (($all_options_matched && isset($switch_scope->new_assigned_var_ids[$var_id]))
                    || !isset($context->vars_in_scope[$var_id])
                ) {
                    $context->unreferenced_vars[$var_id] = $locations;
                } elseif (isset($switch_scope->new_possibly_assigned_var_ids[$var_id])) {
                    if (!isset($context->unreferenced_vars[$var_id])) {
                        $context->unreferenced_vars[$var_id] = $locations;
                    } else {
                        $context->unreferenced_vars[$var_id] += $locations;
                    }
                } else {
                    $statements_analyzer->registerVariableUses($locations);
                }
            }
        }

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $switch_scope->new_vars_possibly_in_scope
        );

        return null;
    }

    /**
     * @param ?string $switch_var_id
     * @return null|false
     */
    private static function handleCase(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Stmt\Switch_ $stmt,
        $switch_var_id,
        PhpParser\Node\Stmt\Case_ $case,
        Context $context,
        Context $original_context,
        string $case_exit_type,
        array $case_actions,
        bool $is_last,
        SwitchScope $switch_scope
    ) {
        // has a return/throw at end
        $has_ending_statements = $case_actions === [ScopeAnalyzer::ACTION_END];
        $has_leaving_statements = $has_ending_statements
            || (count($case_actions) && !in_array(ScopeAnalyzer::ACTION_NONE, $case_actions, true));

        $case_context = clone $original_context;
        if ($codebase->alter_code) {
            $case_context->branch_point = $case_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
        }

        $case_context->parent_context = $context;
        $case_scope = $case_context->case_scope = new CaseScope();
        $case_scope->parent_context = $case_context;

        $case_equality_expr = null;

        if ($case->cond) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $case->cond, $case_context) === false) {
                return false;
            }

            $switch_condition = clone $stmt->cond;

            if ($switch_condition instanceof PhpParser\Node\Expr\Variable
                && is_string($switch_condition->name)
                && isset($context->vars_in_scope['$' . $switch_condition->name])
            ) {
                $switch_var_type = $context->vars_in_scope['$' . $switch_condition->name];

                $type_statements = [];

                foreach ($switch_var_type->getTypes() as $type) {
                    if ($type instanceof Type\Atomic\GetClassT) {
                        $type_statements[] = new PhpParser\Node\Expr\FuncCall(
                            new PhpParser\Node\Name(['get_class']),
                            [
                                new PhpParser\Node\Arg(
                                    new PhpParser\Node\Expr\Variable(substr($type->typeof, 1))
                                ),
                            ]
                        );
                    } elseif ($type instanceof Type\Atomic\GetTypeT) {
                        $type_statements[] = new PhpParser\Node\Expr\FuncCall(
                            new PhpParser\Node\Name(['gettype']),
                            [
                                new PhpParser\Node\Arg(
                                    new PhpParser\Node\Expr\Variable(substr($type->typeof, 1))
                                ),
                            ]
                        );
                    } else {
                        $type_statements = null;
                        break;
                    }
                }

                if ($type_statements && count($type_statements) === 1) {
                    $switch_condition = $type_statements[0];
                }
            }

            if (isset($switch_condition->inferredType)
                && isset($case->cond->inferredType)
                && (($switch_condition->inferredType->isString() && $case->cond->inferredType->isString())
                    || ($switch_condition->inferredType->isInt() && $case->cond->inferredType->isInt())
                    || ($switch_condition->inferredType->isFloat() && $case->cond->inferredType->isFloat())
                )
            ) {
                $case_equality_expr = new PhpParser\Node\Expr\BinaryOp\Identical(
                    $switch_condition,
                    $case->cond,
                    $case->cond->getAttributes()
                );
            } else {
                $case_equality_expr = new PhpParser\Node\Expr\BinaryOp\Equal(
                    $switch_condition,
                    $case->cond,
                    $case->cond->getAttributes()
                );
            }
        }

        $continue_case_equality_expr = false;

        if ($case->stmts) {
            $case_stmts = array_merge($switch_scope->leftover_statements, $case->stmts);
        } else {
            $continue_case_equality_expr = count($switch_scope->leftover_statements) === 1;
            $case_stmts = $switch_scope->leftover_statements;
        }

        if (!$has_leaving_statements && !$is_last) {
            if (!$case_equality_expr) {
                $case_equality_expr = new PhpParser\Node\Expr\FuncCall(
                    new PhpParser\Node\Name\FullyQualified(['rand']),
                    [
                        new PhpParser\Node\Arg(new PhpParser\Node\Scalar\LNumber(0)),
                        new PhpParser\Node\Arg(new PhpParser\Node\Scalar\LNumber(1)),
                    ],
                    $case->getAttributes()
                );
            }

            $switch_scope->leftover_case_equality_expr = $switch_scope->leftover_case_equality_expr
                ? new PhpParser\Node\Expr\BinaryOp\BooleanOr(
                    $switch_scope->leftover_case_equality_expr,
                    $case_equality_expr,
                    $case->cond ? $case->cond->getAttributes() : $case->getAttributes()
                )
                : $case_equality_expr;

            if ($continue_case_equality_expr
                && $switch_scope->leftover_statements[0] instanceof PhpParser\Node\Stmt\If_
            ) {
                $case_if_stmt = $switch_scope->leftover_statements[0];
                $case_if_stmt->cond = $switch_scope->leftover_case_equality_expr;
            } else {
                $case_if_stmt = new PhpParser\Node\Stmt\If_(
                    $switch_scope->leftover_case_equality_expr,
                    ['stmts' => $case_stmts]
                );

                $switch_scope->leftover_statements = [$case_if_stmt];
            }

            return;
        }

        if ($switch_scope->leftover_case_equality_expr) {
            $case_or_default_equality_expr = $case_equality_expr;

            if (!$case_or_default_equality_expr) {
                $case_or_default_equality_expr = new PhpParser\Node\Expr\FuncCall(
                    new PhpParser\Node\Name\FullyQualified(['rand']),
                    [
                        new PhpParser\Node\Arg(new PhpParser\Node\Scalar\LNumber(0)),
                        new PhpParser\Node\Arg(new PhpParser\Node\Scalar\LNumber(1)),
                    ],
                    $case->getAttributes()
                );
            }

            $case_equality_expr = new PhpParser\Node\Expr\BinaryOp\BooleanOr(
                $switch_scope->leftover_case_equality_expr,
                $case_or_default_equality_expr,
                $case_or_default_equality_expr->getAttributes()
            );
        }

        $case_context->inside_case = true;

        $switch_scope->leftover_statements = [];
        $switch_scope->leftover_case_equality_expr = null;

        $case_clauses = [];

        if ($case_equality_expr) {
            $case_clauses = Algebra::getFormula(
                $case_equality_expr,
                $context->self,
                $statements_analyzer,
                $codebase
            );
        }

        if ($switch_scope->negated_clauses) {
            $entry_clauses = Algebra::simplifyCNF(
                array_merge(
                    $original_context->clauses,
                    $switch_scope->negated_clauses
                )
            );
        } else {
            $entry_clauses = $original_context->clauses;
        }

        if ($case_clauses && $case->cond) {
            // this will see whether any of the clauses in set A conflict with the clauses in set B
            AlgebraAnalyzer::checkForParadox(
                $entry_clauses,
                $case_clauses,
                $statements_analyzer,
                $case->cond,
                []
            );

            $case_context->clauses = Algebra::simplifyCNF(array_merge($entry_clauses, $case_clauses));
        } else {
            $case_context->clauses = $entry_clauses;
        }

        $reconcilable_if_types = Algebra::getTruthsFromFormula($case_context->clauses);

        // if the if has an || in the conditional, we cannot easily reason about it
        if ($reconcilable_if_types) {
            $changed_var_ids = [];

            $suppressed_issues = $statements_analyzer->getSuppressedIssues();

            if (!in_array('RedundantCondition', $suppressed_issues, true)) {
                $statements_analyzer->addSuppressedIssues(['RedundantCondition']);
            }

            if (!in_array('RedundantConditionGivenDocblockType', $suppressed_issues, true)) {
                $statements_analyzer->addSuppressedIssues(['RedundantConditionGivenDocblockType']);
            }

            $case_vars_in_scope_reconciled =
                Reconciler::reconcileKeyedTypes(
                    $reconcilable_if_types,
                    $case_context->vars_in_scope,
                    $changed_var_ids,
                    $case->cond && $switch_var_id ? [$switch_var_id => true] : [],
                    $statements_analyzer,
                    new CodeLocation(
                        $statements_analyzer->getSource(),
                        $case->cond ? $case->cond : $case,
                        $context->include_location
                    )
                );

            if (!in_array('RedundantCondition', $suppressed_issues, true)) {
                $statements_analyzer->removeSuppressedIssues(['RedundantCondition']);
            }

            if (!in_array('RedundantConditionGivenDocblockType', $suppressed_issues, true)) {
                $statements_analyzer->removeSuppressedIssues(['RedundantConditionGivenDocblockType']);
            }

            $case_context->vars_in_scope = $case_vars_in_scope_reconciled;
            foreach ($reconcilable_if_types as $var_id => $_) {
                $case_context->vars_possibly_in_scope[$var_id] = true;
            }

            if ($changed_var_ids) {
                $case_context->removeReconciledClauses($changed_var_ids);
            }
        }

        if ($case_clauses) {
            $switch_scope->negated_clauses = array_merge(
                $switch_scope->negated_clauses,
                Algebra::negateFormula($case_clauses)
            );
        }

        $pre_possibly_assigned_var_ids = $case_context->possibly_assigned_var_ids;
        $case_context->possibly_assigned_var_ids = [];

        $pre_assigned_var_ids = $case_context->assigned_var_ids;
        $case_context->assigned_var_ids = [];

        $statements_analyzer->analyze($case_stmts, $case_context);

        /** @var array<string, bool> */
        $new_case_assigned_var_ids = $case_context->assigned_var_ids;
        $case_context->assigned_var_ids = $pre_assigned_var_ids + $new_case_assigned_var_ids;

        /** @var array<string, bool> */
        $new_case_possibly_assigned_var_ids = $case_context->possibly_assigned_var_ids;
        $case_context->possibly_assigned_var_ids =
            $pre_possibly_assigned_var_ids + $new_case_possibly_assigned_var_ids;

        $context->referenced_var_ids = array_merge(
            $context->referenced_var_ids,
            $case_context->referenced_var_ids
        );

        if ($case_exit_type !== 'return_throw') {
            if (self::handleNonReturningCase(
                $statements_analyzer,
                $switch_var_id,
                $case,
                $context,
                $case_context,
                $original_context,
                $new_case_assigned_var_ids,
                $new_case_possibly_assigned_var_ids,
                $case_exit_type,
                $switch_scope,
                $case_scope
            ) === false) {
                return false;
            }
        }

        // augment the information with data from break statements
        if ($case_scope->break_vars !== null) {
            if ($switch_scope->possibly_redefined_vars === null) {
                $switch_scope->possibly_redefined_vars = array_intersect_key(
                    $case_scope->break_vars,
                    $context->vars_in_scope
                );
            } else {
                foreach ($case_scope->break_vars as $var_id => $type) {
                    if (isset($context->vars_in_scope[$var_id])) {
                        if (!isset($switch_scope->possibly_redefined_vars[$var_id])) {
                            $switch_scope->possibly_redefined_vars[$var_id] = $type;
                        } else {
                            $switch_scope->possibly_redefined_vars[$var_id] = Type::combineUnionTypes(
                                $type,
                                $switch_scope->possibly_redefined_vars[$var_id]
                            );
                        }
                    }
                }
            }

            if ($switch_scope->new_vars_in_scope !== null) {
                foreach ($switch_scope->new_vars_in_scope as $var_id => $type) {
                    if (isset($case_scope->break_vars[$var_id])) {
                        if (!isset($case_context->vars_in_scope[$var_id])) {
                            unset($switch_scope->new_vars_in_scope[$var_id]);
                        } else {
                            $switch_scope->new_vars_in_scope[$var_id] = Type::combineUnionTypes(
                                $case_scope->break_vars[$var_id],
                                $type
                            );
                        }
                    } else {
                        unset($switch_scope->new_vars_in_scope[$var_id]);
                    }
                }
            }

            if ($switch_scope->redefined_vars !== null) {
                foreach ($switch_scope->redefined_vars as $var_id => $type) {
                    if (isset($case_scope->break_vars[$var_id])) {
                        $switch_scope->redefined_vars[$var_id] = Type::combineUnionTypes(
                            $case_scope->break_vars[$var_id],
                            $type
                        );
                    } else {
                        unset($switch_scope->redefined_vars[$var_id]);
                    }
                }
            }
        }

        $case_context->case_scope = null;
    }

    /**
     * @param ?string $switch_var_id
     * @param array<string, bool> $new_case_assigned_var_ids
     * @param array<string, bool> $new_case_possibly_assigned_var_ids
     * @return null|false
     */
    private static function handleNonReturningCase(
        StatementsAnalyzer $statements_analyzer,
        $switch_var_id,
        PhpParser\Node\Stmt\Case_ $case,
        Context $context,
        Context $case_context,
        Context $original_context,
        array $new_case_assigned_var_ids,
        array $new_case_possibly_assigned_var_ids,
        string $case_exit_type,
        SwitchScope $switch_scope,
        CaseScope $case_scope
    ) {
        if (!$case->cond
            && $switch_var_id
            && isset($case_context->vars_in_scope[$switch_var_id])
            && $case_context->vars_in_scope[$switch_var_id]->isEmpty()
        ) {
            if (IssueBuffer::accepts(
                new ParadoxicalCondition(
                    'All possible case statements have been met, default is impossible here',
                    new CodeLocation($statements_analyzer->getSource(), $case)
                )
            )) {
                return false;
            }
        }

        $vars = array_diff_key(
            $case_context->vars_possibly_in_scope,
            $original_context->vars_possibly_in_scope
        );

        // if we're leaving this block, add vars to outer for loop scope
        if ($case_exit_type === 'continue') {
            if ($context->loop_scope) {
                $context->loop_scope->vars_possibly_in_scope = array_merge(
                    $vars,
                    $context->loop_scope->vars_possibly_in_scope
                );
            } else {
                if (IssueBuffer::accepts(
                    new ContinueOutsideLoop(
                        'Continue called when not in loop',
                        new CodeLocation($statements_analyzer->getSource(), $case)
                    )
                )) {
                    return false;
                }
            }
        } else {
            $case_redefined_vars = $case_context->getRedefinedVars($original_context->vars_in_scope);

            if ($switch_scope->possibly_redefined_vars === null) {
                $switch_scope->possibly_redefined_vars = $case_redefined_vars;
            } else {
                foreach ($case_redefined_vars as $var_id => $type) {
                    if (!isset($switch_scope->possibly_redefined_vars[$var_id])) {
                        $switch_scope->possibly_redefined_vars[$var_id] = $type;
                    } else {
                        $switch_scope->possibly_redefined_vars[$var_id] = Type::combineUnionTypes(
                            $type,
                            $switch_scope->possibly_redefined_vars[$var_id]
                        );
                    }
                }
            }

            if ($switch_scope->redefined_vars === null) {
                $switch_scope->redefined_vars = $case_redefined_vars;
            } else {
                foreach ($switch_scope->redefined_vars as $var_id => $type) {
                    if (!isset($case_redefined_vars[$var_id])) {
                        unset($switch_scope->redefined_vars[$var_id]);
                    } else {
                        $switch_scope->redefined_vars[$var_id] = Type::combineUnionTypes(
                            $type,
                            $case_redefined_vars[$var_id]
                        );
                    }
                }
            }

            $context_new_vars = array_diff_key($case_context->vars_in_scope, $context->vars_in_scope);

            if ($switch_scope->new_vars_in_scope === null) {
                $switch_scope->new_vars_in_scope = $context_new_vars;
                $switch_scope->new_vars_possibly_in_scope = array_diff_key(
                    $case_context->vars_possibly_in_scope,
                    $context->vars_possibly_in_scope
                );
            } else {
                foreach ($switch_scope->new_vars_in_scope as $new_var => $type) {
                    if (!$case_context->hasVariable($new_var, $statements_analyzer)) {
                        unset($switch_scope->new_vars_in_scope[$new_var]);
                    } else {
                        $switch_scope->new_vars_in_scope[$new_var] =
                            Type::combineUnionTypes($case_context->vars_in_scope[$new_var], $type);
                    }
                }

                $switch_scope->new_vars_possibly_in_scope = array_merge(
                    array_diff_key(
                        $case_context->vars_possibly_in_scope,
                        $context->vars_possibly_in_scope
                    ),
                    $switch_scope->new_vars_possibly_in_scope
                );
            }
        }

        if ($context->collect_exceptions) {
            $context->possibly_thrown_exceptions += $case_context->possibly_thrown_exceptions;
        }

        if ($context->collect_references) {
            $switch_scope->new_possibly_assigned_var_ids =
                $switch_scope->new_possibly_assigned_var_ids + $new_case_possibly_assigned_var_ids;

            if ($switch_scope->new_assigned_var_ids === null) {
                $switch_scope->new_assigned_var_ids = $new_case_assigned_var_ids;
            } else {
                $switch_scope->new_assigned_var_ids = array_intersect_key(
                    $switch_scope->new_assigned_var_ids,
                    $new_case_assigned_var_ids
                );
            }

            foreach ($case_context->unreferenced_vars as $var_id => $locations) {
                if (!isset($original_context->unreferenced_vars[$var_id])) {
                    if (isset($switch_scope->new_unreferenced_vars[$var_id])) {
                        $switch_scope->new_unreferenced_vars[$var_id] += $locations;
                    } else {
                        $switch_scope->new_unreferenced_vars[$var_id] = $locations;
                    }
                } else {
                    $new_locations = array_diff_key(
                        $locations,
                        $original_context->unreferenced_vars[$var_id]
                    );

                    if ($new_locations) {
                        if (isset($switch_scope->new_unreferenced_vars[$var_id])) {
                            $switch_scope->new_unreferenced_vars[$var_id] += $locations;
                        } else {
                            $switch_scope->new_unreferenced_vars[$var_id] = $locations;
                        }
                    }
                }
            }

            foreach ($case_scope->unreferenced_vars as $var_id => $locations) {
                if (!isset($original_context->unreferenced_vars[$var_id])) {
                    if (isset($switch_scope->new_unreferenced_vars[$var_id])) {
                        $switch_scope->new_unreferenced_vars[$var_id] += $locations;
                    } else {
                        $switch_scope->new_unreferenced_vars[$var_id] = $locations;
                    }
                } else {
                    $new_locations = array_diff_key(
                        $locations,
                        $original_context->unreferenced_vars[$var_id]
                    );

                    if ($new_locations) {
                        if (isset($switch_scope->new_unreferenced_vars[$var_id])) {
                            $switch_scope->new_unreferenced_vars[$var_id] += $locations;
                        } else {
                            $switch_scope->new_unreferenced_vars[$var_id] = $locations;
                        }
                    }
                }
            }
        }
    }
}
