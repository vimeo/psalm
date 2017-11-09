<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\AlgebraChecker;
use Psalm\Checker\ScopeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ContinueOutsideLoop;
use Psalm\IssueBuffer;
use Psalm\Type;

class SwitchChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Stmt\Switch_     $stmt
     * @param   Context                         $context
     * @param   Context|null                    $loop_context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\Switch_ $stmt,
        Context $context,
        Context $loop_context = null
    ) {
        if (ExpressionChecker::analyze($statements_checker, $stmt->cond, $context) === false) {
            return false;
        }

        $original_context = clone $context;

        $new_vars_in_scope = null;

        $new_vars_possibly_in_scope = [];

        $redefined_vars = null;
        $possibly_redefined_vars = null;

        // the last statement always breaks, by default
        $last_case_exit_type = 'break';

        $case_exit_types = new \SplFixedArray(count($stmt->cases));

        $has_default = false;

        // create a map of case statement -> ultimate exit type
        for ($i = count($stmt->cases) - 1; $i >= 0; --$i) {
            $case = $stmt->cases[$i];

            if (ScopeChecker::doesAlwaysReturnOrThrow($case->stmts)) {
                $last_case_exit_type = 'return_throw';
            } elseif (ScopeChecker::doesAlwaysBreakOrContinue($case->stmts, true)) {
                $last_case_exit_type = 'continue';
            } elseif (ScopeChecker::doesAlwaysBreakOrContinue($case->stmts)) {
                $last_case_exit_type = 'break';
            }

            $case_exit_types[$i] = $last_case_exit_type;
        }

        $leftover_statements = [];

        for ($i = count($stmt->cases) - 1; $i >= 0; --$i) {
            $case = $stmt->cases[$i];
            /** @var string */
            $case_exit_type = $case_exit_types[$i];

            $case_context = clone $original_context;
            $case_context->parent_context = $context;

            if ($case->cond) {
                if (ExpressionChecker::analyze($statements_checker, $case->cond, $context) === false) {
                    return false;
                }

                $fake_equality = new PhpParser\Node\Expr\BinaryOp\Equal($stmt->cond, $case->cond);

                $case_clauses = AlgebraChecker::getFormula(
                    $fake_equality,
                    $context->self,
                    $statements_checker
                );

                // this will see whether any of the clauses in set A conflict with the clauses in set B
                AlgebraChecker::checkForParadox($context->clauses, $case_clauses, $statements_checker, $stmt->cond);

                $case_context->clauses = AlgebraChecker::simplifyCNF(array_merge($context->clauses, $case_clauses));

                $reconcilable_if_types = AlgebraChecker::getTruthsFromFormula($case_context->clauses);

                // if the if has an || in the conditional, we cannot easily reason about it
                if ($reconcilable_if_types) {
                    $changed_vars = [];

                    $case_vars_in_scope_reconciled =
                        TypeChecker::reconcileKeyedTypes(
                            $reconcilable_if_types,
                            $case_context->vars_in_scope,
                            $changed_vars,
                            $statements_checker,
                            new CodeLocation($statements_checker->getSource(), $stmt->cond, $context->include_location),
                            $statements_checker->getSuppressedIssues()
                        );

                    if ($case_vars_in_scope_reconciled === false) {
                        return false;
                    }

                    $case_context->vars_in_scope = $case_vars_in_scope_reconciled;
                    $case_context->vars_possibly_in_scope = array_merge(
                        $reconcilable_if_types,
                        $case_context->vars_possibly_in_scope
                    );
                }
            }

            $case_stmts = $case->stmts;

            // has a return/throw at end
            $has_ending_statements = ScopeChecker::doesAlwaysReturnOrThrow($case_stmts);
            $has_leaving_statements = ScopeChecker::doesAlwaysBreakOrContinue($case_stmts);

            if (!$case_stmts || (!$has_ending_statements && !$has_leaving_statements)) {
                $case_stmts = array_merge($case_stmts, $leftover_statements);
            } else {
                $leftover_statements = [];
            }

            $statements_checker->analyze($case_stmts, $case_context, $loop_context);

            if ($context->collect_references) {
                $context->referenced_vars = array_merge(
                    $context->referenced_vars,
                    $case_context->referenced_vars
                );
            }

            if ($case_exit_type !== 'return_throw') {
                $vars = array_diff_key(
                    $case_context->vars_possibly_in_scope,
                    $original_context->vars_possibly_in_scope
                );

                // if we're leaving this block, add vars to outer for loop scope
                if ($case_exit_type === 'continue') {
                    if ($loop_context) {
                        $loop_context->vars_possibly_in_scope = array_merge(
                            $vars,
                            $loop_context->vars_possibly_in_scope
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
                    $case_redefined_vars = $case_context->getRedefinedVars($original_context);

                    Type::redefineGenericUnionTypes($case_redefined_vars, $context);

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
                        foreach ($redefined_vars as $redefined_var => $type) {
                            if (!isset($case_redefined_vars[$redefined_var])) {
                                unset($redefined_vars[$redefined_var]);
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
                            if (!$case_context->hasVariable($new_var)) {
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

            if ($case->stmts) {
                $leftover_statements = array_merge($leftover_statements, $case->stmts);
            }

            if (!$case->cond) {
                $has_default = true;
            }
        }

        // only update vars if there is a default
        // if that default has a throw/return/continue, that should be handled above
        if ($has_default) {
            if ($new_vars_in_scope) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $new_vars_in_scope);
            }

            if ($redefined_vars) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $redefined_vars);
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
