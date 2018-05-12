<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\AlgebraChecker;
use Psalm\Checker\ScopeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ContinueOutsideLoop;
use Psalm\Issue\ParadoxicalCondition;
use Psalm\IssueBuffer;
use Psalm\Scope\LoopScope;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Reconciler;

class SwitchChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Stmt\Switch_     $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\Switch_ $stmt,
        Context $context,
        LoopScope $loop_scope = null
    ) {
        if (ExpressionChecker::analyze($statements_checker, $stmt->cond, $context) === false) {
            return false;
        }

        $switch_var_id = ExpressionChecker::getArrayVarId(
            $stmt->cond,
            null,
            $statements_checker
        );

        $original_context = clone $context;

        $new_vars_in_scope = null;

        $new_vars_possibly_in_scope = [];

        $redefined_vars = null;
        $possibly_redefined_vars = null;

        // the last statement always breaks, by default
        $last_case_exit_type = 'break';

        $case_exit_types = new \SplFixedArray(count($stmt->cases));

        $has_default = false;

        $case_action_map = [];

        // create a map of case statement -> ultimate exit type
        for ($i = count($stmt->cases) - 1; $i >= 0; --$i) {
            $case = $stmt->cases[$i];

            $case_actions = $case_action_map[$i] = ScopeChecker::getFinalControlActions($case->stmts, true);

            if (!in_array(ScopeChecker::ACTION_NONE, $case_actions, true)) {
                if ($case_actions === [ScopeChecker::ACTION_END]) {
                    $last_case_exit_type = 'return_throw';
                } elseif ($case_actions === [ScopeChecker::ACTION_CONTINUE]) {
                    $last_case_exit_type = 'continue';
                } elseif (in_array(ScopeChecker::ACTION_BREAK, $case_actions, true)) {
                    $last_case_exit_type = 'break';
                }
            }

            $case_exit_types[$i] = $last_case_exit_type;
        }

        $leftover_statements = [];
        $leftover_case_equality_expr = null;
        $negated_clauses = [];

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        for ($i = 0, $l = count($stmt->cases); $i < $l; $i++) {
            $case = $stmt->cases[$i];

            /** @var string */
            $case_exit_type = $case_exit_types[$i];

            $case_actions = $case_action_map[$i];

            // has a return/throw at end
            $has_ending_statements = $case_actions === [ScopeChecker::ACTION_END];
            $has_leaving_statements = $has_ending_statements
                || (count($case_actions) && !in_array(ScopeChecker::ACTION_NONE, $case_actions, true));

            $case_context = clone $original_context;
            if ($project_checker->alter_code) {
                $case_context->branch_point = $case_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
            }
            $case_context->parent_context = $context;

            $case_equality_expr = null;

            if ($case->cond) {
                if (ExpressionChecker::analyze($statements_checker, $case->cond, $case_context) === false) {
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

            $case_stmts = $case->stmts;

            $case_stmts = array_merge($leftover_statements, $case_stmts);

            if (!$case->cond) {
                $has_default = true;
            }

            if (!$has_leaving_statements && $i !== $l - 1) {
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

                $leftover_case_equality_expr = $leftover_case_equality_expr
                    ? new PhpParser\Node\Expr\BinaryOp\BooleanOr(
                        $leftover_case_equality_expr,
                        $case_equality_expr,
                        $case->cond ? $case->cond->getAttributes() : $case->getAttributes()
                    )
                    : $case_equality_expr;

                $case_if_stmt = new PhpParser\Node\Stmt\If_(
                    $leftover_case_equality_expr,
                    ['stmts' => $case_stmts]
                );

                $leftover_statements = [$case_if_stmt];

                continue;
            }

            if ($leftover_case_equality_expr) {
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
                    $leftover_case_equality_expr,
                    $case_or_default_equality_expr,
                    $case_or_default_equality_expr->getAttributes()
                );
            }

            $case_context->inside_case = true;

            $leftover_statements = [];
            $leftover_case_equality_expr = null;

            $case_clauses = [];

            if ($case_equality_expr) {
                $case_clauses = Algebra::getFormula(
                    $case_equality_expr,
                    $context->self,
                    $statements_checker
                );
            }

            if ($negated_clauses) {
                $entry_clauses = Algebra::simplifyCNF(array_merge($original_context->clauses, $negated_clauses));
            } else {
                $entry_clauses = $original_context->clauses;
            }

            if ($case_clauses) {
                // this will see whether any of the clauses in set A conflict with the clauses in set B
                AlgebraChecker::checkForParadox(
                    $entry_clauses,
                    $case_clauses,
                    $statements_checker,
                    $stmt->cond,
                    []
                );

                $case_context->clauses = Algebra::simplifyCNF(array_merge($entry_clauses, $case_clauses));
            } else {
                $case_context->clauses = $entry_clauses;
            }

            $reconcilable_if_types = Algebra::getTruthsFromFormula($case_context->clauses);

            $printer = new PhpParser\PrettyPrinter\Standard;

            // if the if has an || in the conditional, we cannot easily reason about it
            if ($reconcilable_if_types) {
                $changed_var_ids = [];

                $suppressed_issues = $statements_checker->getSuppressedIssues();

                if (!in_array('RedundantCondition', $suppressed_issues, true)) {
                    $statements_checker->addSuppressedIssues(['RedundantCondition']);
                }

                $case_vars_in_scope_reconciled =
                    Reconciler::reconcileKeyedTypes(
                        $reconcilable_if_types,
                        $case_context->vars_in_scope,
                        $changed_var_ids,
                        $switch_var_id ? [$switch_var_id => true] : [],
                        $statements_checker,
                        new CodeLocation($statements_checker->getSource(), $case, $context->include_location),
                        $statements_checker->getSuppressedIssues()
                    );

                if (!in_array('RedundantCondition', $suppressed_issues, true)) {
                    $statements_checker->removeSuppressedIssues(['RedundantCondition']);
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
                $negated_clauses = array_merge(
                    $negated_clauses,
                    Algebra::negateFormula($case_clauses)
                );
            }

            $statements_checker->analyze($case_stmts, $case_context, $loop_scope);

            $context->referenced_var_ids = array_merge(
                $context->referenced_var_ids,
                $case_context->referenced_var_ids
            );

            if ($case_exit_type !== 'return_throw') {
                if (!$case->cond
                    && $switch_var_id
                    && isset($case_context->vars_in_scope[$switch_var_id])
                    && $case_context->vars_in_scope[$switch_var_id]->isEmpty()
                ) {
                    if (IssueBuffer::accepts(
                        new ParadoxicalCondition(
                            'All possible case statements have been met, default is impossible here',
                            new CodeLocation($statements_checker->getSource(), $case)
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
                    if ($loop_scope) {
                        $loop_scope->vars_possibly_in_scope = array_merge(
                            $vars,
                            $loop_scope->vars_possibly_in_scope
                        );
                    } else {
                        if (IssueBuffer::accepts(
                            new ContinueOutsideLoop(
                                'Continue called when not in loop',
                                new CodeLocation($statements_checker->getSource(), $case)
                            )
                        )) {
                            return false;
                        }
                    }
                } else {
                    $case_redefined_vars = $case_context->getRedefinedVars($original_context->vars_in_scope);

                    if ($possibly_redefined_vars === null) {
                        $possibly_redefined_vars = $case_redefined_vars;
                    } else {
                        foreach ($case_redefined_vars as $var_id => $type) {
                            if (!isset($possibly_redefined_vars[$var_id])) {
                                $possibly_redefined_vars[$var_id] = $type;
                            } else {
                                $possibly_redefined_vars[$var_id] = Type::combineUnionTypes(
                                    $type,
                                    $possibly_redefined_vars[$var_id]
                                );
                            }
                        }
                    }

                    if ($redefined_vars === null) {
                        $redefined_vars = $case_redefined_vars;
                    } else {
                        foreach ($redefined_vars as $var_id => $type) {
                            if (!isset($case_redefined_vars[$var_id])) {
                                unset($redefined_vars[$var_id]);
                            } else {
                                $redefined_vars[$var_id] = Type::combineUnionTypes(
                                    $type,
                                    $case_redefined_vars[$var_id]
                                );
                            }
                        }
                    }

                    $context_new_vars = array_diff_key($case_context->vars_in_scope, $context->vars_in_scope);

                    if ($new_vars_in_scope === null) {
                        $new_vars_in_scope = $context_new_vars;
                        $new_vars_possibly_in_scope = array_diff_key(
                            $case_context->vars_possibly_in_scope,
                            $context->vars_possibly_in_scope
                        );
                    } else {
                        foreach ($new_vars_in_scope as $new_var => $type) {
                            if (!$case_context->hasVariable($new_var, $statements_checker)) {
                                unset($new_vars_in_scope[$new_var]);
                            } else {
                                $new_vars_in_scope[$new_var] =
                                    Type::combineUnionTypes($case_context->vars_in_scope[$new_var], $type);
                            }
                        }

                        $new_vars_possibly_in_scope = array_merge(
                            array_diff_key(
                                $case_context->vars_possibly_in_scope,
                                $context->vars_possibly_in_scope
                            ),
                            $new_vars_possibly_in_scope
                        );
                    }
                }
            }

            if ($context->collect_references) {
                foreach ($case_context->unreferenced_vars as $var_id => $location) {
                    if (isset($context->unreferenced_vars[$var_id])
                        && $context->unreferenced_vars[$var_id] !== $location
                    ) {
                        $context->hasVariable($var_id, $statements_checker);
                    }
                }

                $context->unreferenced_vars = array_merge(
                    $context->unreferenced_vars,
                    $case_context->unreferenced_vars
                );
            }
        }

        $all_options_matched = $has_default;

        if (!$has_default && $negated_clauses && $switch_var_id) {
            $entry_clauses = Algebra::simplifyCNF(array_merge($original_context->clauses, $negated_clauses));

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
                        $statements_checker
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
            if ($new_vars_in_scope) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $new_vars_in_scope);
            }

            if ($redefined_vars) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $redefined_vars);
            }

            if ($possibly_redefined_vars) {
                foreach ($possibly_redefined_vars as $var_id => $type) {
                    if (!isset($redefined_vars[$var_id]) && !isset($new_vars_in_scope[$var_id])) {
                        $context->vars_in_scope[$var_id]
                            = Type::combineUnionTypes($type, $context->vars_in_scope[$var_id]);
                    }
                }
            }
        } elseif ($possibly_redefined_vars) {
            foreach ($possibly_redefined_vars as $var_id => $type) {
                $context->vars_in_scope[$var_id] = Type::combineUnionTypes($type, $context->vars_in_scope[$var_id]);
            }
        }

        $context->vars_possibly_in_scope = array_merge($context->vars_possibly_in_scope, $new_vars_possibly_in_scope);

        return null;
    }
}
