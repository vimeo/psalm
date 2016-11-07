<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\ScopeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\Context;
use Psalm\Type;

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
     * @param  Context|null            $loop_context
     * @return null|false
     */
    public static function check(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\If_ $stmt,
        Context $context,
        Context $loop_context = null
    ) {
        // get the first expression in the if, which should be evaluated on its own
        // this allows us to update the context of $matches in
        // if (!preg_match('/a/', 'aa', $matches)) {
        //   exit
        // }
        // echo $matches[0];
        $first_if_cond_expr = self::getFirstFunctionCall($stmt->cond);

        if ($first_if_cond_expr &&
            ExpressionChecker::check($statements_checker, $first_if_cond_expr, $context) === false
        ) {
            return false;
        }

        $if_context = clone $context;

        // we need to clone the current context so our ongoing updates to $context don't mess with elseif/else blocks
        $original_context = clone $context;

        if ($first_if_cond_expr !== $stmt->cond &&
            ExpressionChecker::check($statements_checker, $stmt->cond, $if_context) === false
        ) {
            return false;
        }

        $reconcilable_if_types = null;
        $negatable_if_types = null;

        if ($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp) {
            $reconcilable_if_types = TypeChecker::getReconcilableTypeAssertions(
                $stmt->cond,
                $statements_checker->getFullyQualifiedClass(),
                $statements_checker->getNamespace(),
                $statements_checker->getAliasedClasses()
            );

            $negatable_if_types = TypeChecker::getNegatableTypeAssertions(
                $stmt->cond,
                $statements_checker->getFullyQualifiedClass(),
                $statements_checker->getNamespace(),
                $statements_checker->getAliasedClasses()
            );
        } else {
            $reconcilable_if_types = $negatable_if_types = TypeChecker::getTypeAssertions(
                $stmt->cond,
                $statements_checker->getFullyQualifiedClass(),
                $statements_checker->getNamespace(),
                $statements_checker->getAliasedClasses()
            );
        }

        $has_ending_statements = ScopeChecker::doesAlwaysReturnOrThrow($stmt->stmts);

        $has_leaving_statements = $has_ending_statements || ScopeChecker::doesAlwaysBreakOrContinue($stmt->stmts);

        $negated_types = $negatable_if_types ? TypeChecker::negateTypes($negatable_if_types) : [];

        $negated_if_types = $negated_types;

        // if the if has an || in the conditional, we cannot easily reason about it
        if ($reconcilable_if_types) {
            $if_vars_in_scope_reconciled =
                TypeChecker::reconcileKeyedTypes(
                    $reconcilable_if_types,
                    $if_context->vars_in_scope,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine(),
                    $statements_checker->getSuppressedIssues()
                );

            if ($if_vars_in_scope_reconciled === false) {
                return false;
            }

            $if_context->vars_in_scope = $if_vars_in_scope_reconciled;
            $if_context->vars_possibly_in_scope = array_merge(
                $reconcilable_if_types,
                $if_context->vars_possibly_in_scope
            );
        }

        $old_if_context = clone $if_context;
        $context->vars_possibly_in_scope = array_merge(
            $if_context->vars_possibly_in_scope,
            $context->vars_possibly_in_scope
        );

        $else_context = clone $original_context;

        if ($negated_types) {
            $else_vars_reconciled = TypeChecker::reconcileKeyedTypes(
                $negated_types,
                $else_context->vars_in_scope,
                $statements_checker->getCheckedFileName(),
                $stmt->getLine(),
                $statements_checker->getSuppressedIssues()
            );

            if ($else_vars_reconciled === false) {
                return false;
            }

            $else_context->vars_in_scope = $else_vars_reconciled;
        }

        // we calculate the vars redefined in a hypothetical else statement to determine
        // which vars of the if we can safely change
        $pre_assignment_else_redefined_vars = Context::getRedefinedVars($context, $else_context);

        if ($statements_checker->check($stmt->stmts, $if_context, $loop_context) === false) {
            return false;
        }

        $forced_new_vars = null;
        $new_vars = null;
        $new_vars_possibly_in_scope = [];
        $redefined_vars = null;
        $possibly_redefined_vars = [];

        $redefined_loop_vars = null;
        $possibly_redefined_loop_vars = [];

        $updated_vars = [];
        $updated_loop_vars = [];

        $mic_drop = false;

        if (count($stmt->stmts)) {
            if (!$has_leaving_statements) {
                $new_vars = array_diff_key($if_context->vars_in_scope, $context->vars_in_scope);

                // if we have a check like if (!isset($a)) { $a = true; } we want to make sure $a is always set
                foreach ($new_vars as $var_id => $type) {
                    if (isset($negated_if_types[$var_id]) && $negated_if_types[$var_id] === '!null') {
                        $forced_new_vars[$var_id] = Type::getMixed();
                    }
                }

                $redefined_vars = Context::getRedefinedVars($context, $if_context);
                $possibly_redefined_vars = $redefined_vars;
            } elseif (!$stmt->else && !$stmt->elseifs && $negated_types) {
                $context_vars_reconciled = TypeChecker::reconcileKeyedTypes(
                    $negated_types,
                    $context->vars_in_scope,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine(),
                    $statements_checker->getSuppressedIssues()
                );

                if ($context_vars_reconciled === false) {
                    return false;
                }

                $context->vars_in_scope = $context_vars_reconciled;
                $mic_drop = true;
            }

            // update the parent context as necessary, but only if we can safely reason about type negation.
            // We only update vars that changed both at the start of the if block and then again by an assignment
            // in the if statement.
            if ($negatable_if_types && !$mic_drop) {
                $context->update(
                    $old_if_context,
                    $if_context,
                    $has_leaving_statements,
                    array_intersect(array_keys($pre_assignment_else_redefined_vars), array_keys($negatable_if_types)),
                    $updated_vars
                );
            }

            if (!$has_ending_statements) {
                $vars = array_diff_key($if_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);

                if ($has_leaving_statements && $loop_context) {
                    $redefined_loop_vars = Context::getRedefinedVars($loop_context, $if_context);
                    $possibly_redefined_loop_vars = $redefined_loop_vars;
                }

                // if we're leaving this block, add vars to outer for loop scope
                if ($has_leaving_statements) {
                    if ($loop_context) {
                        $loop_context->vars_possibly_in_scope = array_merge(
                            $loop_context->vars_possibly_in_scope,
                            $vars
                        );
                    }
                } else {
                    $new_vars_possibly_in_scope = $vars;
                }
            }
        }

        foreach ($stmt->elseifs as $elseif) {
            $elseif_context = clone $original_context;

            if ($negated_types) {
                $elseif_vars_reconciled = TypeChecker::reconcileKeyedTypes(
                    $negated_types,
                    $elseif_context->vars_in_scope,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine(),
                    $statements_checker->getSuppressedIssues()
                );

                if ($elseif_vars_reconciled === false) {
                    return false;
                }

                $elseif_context->vars_in_scope = $elseif_vars_reconciled;
            }

            if ($elseif->cond instanceof PhpParser\Node\Expr\BinaryOp) {
                $reconcilable_elseif_types = TypeChecker::getReconcilableTypeAssertions(
                    $elseif->cond,
                    $statements_checker->getFullyQualifiedClass(),
                    $statements_checker->getNamespace(),
                    $statements_checker->getAliasedClasses()
                );

                $negatable_elseif_types = TypeChecker::getNegatableTypeAssertions(
                    $elseif->cond,
                    $statements_checker->getFullyQualifiedClass(),
                    $statements_checker->getNamespace(),
                    $statements_checker->getAliasedClasses()
                );
            } else {
                $reconcilable_elseif_types = $negatable_elseif_types = TypeChecker::getTypeAssertions(
                    $elseif->cond,
                    $statements_checker->getFullyQualifiedClass(),
                    $statements_checker->getNamespace(),
                    $statements_checker->getAliasedClasses()
                );
            }

            $negated_elseif_types = $negatable_elseif_types
                                    ? TypeChecker::negateTypes($negatable_elseif_types)
                                    : [];

            $all_negated_vars = array_unique(
                array_merge(
                    array_keys($negated_elseif_types),
                    array_keys($negated_types)
                )
            );

            foreach ($all_negated_vars as $var_id) {
                if (isset($negated_elseif_types[$var_id])) {
                    if (isset($negated_types[$var_id])) {
                        $negated_types[$var_id] = $negated_types[$var_id] . '&' . $negated_elseif_types[$var_id];
                    } else {
                        $negated_types[$var_id] = $negated_elseif_types[$var_id];
                    }
                }
            }

            // if the elseif has an || in the conditional, we cannot easily reason about it
            if ($reconcilable_elseif_types) {
                $elseif_vars_reconciled = TypeChecker::reconcileKeyedTypes(
                    $reconcilable_elseif_types,
                    $elseif_context->vars_in_scope,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine(),
                    $statements_checker->getSuppressedIssues()
                );

                if ($elseif_vars_reconciled === false) {
                    return false;
                }

                $elseif_context->vars_in_scope = $elseif_vars_reconciled;
            }

            // check the elseif
            if (ExpressionChecker::check($statements_checker, $elseif->cond, $elseif_context) === false) {
                return false;
            }

            $old_elseif_context = clone $elseif_context;

            if ($statements_checker->check($elseif->stmts, $elseif_context, $loop_context) === false) {
                return false;
            }

            if (count($elseif->stmts)) {
                // has a return/throw at end
                $has_ending_statements = ScopeChecker::doesAlwaysReturnOrThrow($elseif->stmts);

                $has_leaving_statements = $has_ending_statements ||
                    ScopeChecker::doesAlwaysBreakOrContinue($elseif->stmts);

                // update the parent context as necessary
                $elseif_redefined_vars = Context::getRedefinedVars($original_context, $elseif_context);

                if (!$has_leaving_statements) {
                    if ($new_vars === null) {
                        $new_vars = array_diff_key($elseif_context->vars_in_scope, $context->vars_in_scope);
                    } else {
                        foreach ($new_vars as $new_var => $type) {
                            if (!isset($elseif_context->vars_in_scope[$new_var])) {
                                unset($new_vars[$new_var]);
                            } else {
                                $new_vars[$new_var] = Type::combineUnionTypes(
                                    $type,
                                    $elseif_context->vars_in_scope[$new_var]
                                );
                            }
                        }
                    }

                    if ($redefined_vars === null) {
                        $redefined_vars = $elseif_redefined_vars;
                        $possibly_redefined_vars = $redefined_vars;
                    } else {
                        foreach ($redefined_vars as $redefined_var => $type) {
                            if (!isset($elseif_redefined_vars[$redefined_var])) {
                                unset($redefined_vars[$redefined_var]);
                            } else {
                                $redefined_vars[$redefined_var] = Type::combineUnionTypes(
                                    $elseif_redefined_vars[$redefined_var],
                                    $type
                                );
                            }
                        }

                        foreach ($elseif_redefined_vars as $var => $type) {
                            if ($type->isMixed()) {
                                $possibly_redefined_vars[$var] = $type;
                            } elseif (isset($possibly_redefined_vars[$var])) {
                                $possibly_redefined_vars[$var] = Type::combineUnionTypes(
                                    $type,
                                    $possibly_redefined_vars[$var]
                                );
                            } else {
                                $possibly_redefined_vars[$var] = $type;
                            }
                        }
                    }
                }

                if ($negatable_elseif_types) {
                    $context->update(
                        $old_elseif_context,
                        $elseif_context,
                        $has_leaving_statements,
                        array_keys($negated_elseif_types),
                        $updated_vars
                    );
                }

                if (!$has_ending_statements) {
                    $vars = array_diff_key($elseif_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);

                    // if we're leaving this block, add vars to outer for loop scope
                    if ($has_leaving_statements && $loop_context) {
                        if ($redefined_loop_vars === null) {
                            $redefined_loop_vars = $elseif_redefined_vars;
                            $possibly_redefined_loop_vars = $redefined_loop_vars;
                        } else {
                            foreach ($redefined_loop_vars as $redefined_var => $type) {
                                if (!isset($elseif_redefined_vars[$redefined_var])) {
                                    unset($redefined_loop_vars[$redefined_var]);
                                } else {
                                    $redefined_loop_vars[$redefined_var] = Type::combineUnionTypes(
                                        $elseif_redefined_vars[$redefined_var],
                                        $type
                                    );
                                }
                            }

                            foreach ($elseif_redefined_vars as $var => $type) {
                                if ($type->isMixed()) {
                                    $possibly_redefined_loop_vars[$var] = $type;
                                } elseif (isset($possibly_redefined_loop_vars[$var])) {
                                    $possibly_redefined_loop_vars[$var] = Type::combineUnionTypes(
                                        $type,
                                        $possibly_redefined_loop_vars[$var]
                                    );
                                } else {
                                    $possibly_redefined_loop_vars[$var] = $type;
                                }
                            }
                        }

                        $loop_context->vars_possibly_in_scope = array_merge(
                            $vars,
                            $loop_context->vars_possibly_in_scope
                        );
                    } elseif (!$has_leaving_statements) {
                        $new_vars_possibly_in_scope = array_merge($vars, $new_vars_possibly_in_scope);
                    }
                }
            }
        }

        if ($stmt->else) {
            $else_context = clone $original_context;

            if ($negated_types) {
                $else_vars_reconciled = TypeChecker::reconcileKeyedTypes(
                    $negated_types,
                    $else_context->vars_in_scope,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine(),
                    $statements_checker->getSuppressedIssues()
                );

                if ($else_vars_reconciled === false) {
                    return false;
                }

                $else_context->vars_in_scope = $else_vars_reconciled;
            }

            $old_else_context = clone $else_context;

            if ($statements_checker->check($stmt->else->stmts, $else_context, $loop_context) === false) {
                return false;
            }

            if (count($stmt->else->stmts)) {
                // has a return/throw at end
                $has_ending_statements = ScopeChecker::doesAlwaysReturnOrThrow($stmt->else->stmts);

                $has_leaving_statements = $has_ending_statements ||
                    ScopeChecker::doesAlwaysBreakOrContinue($stmt->else->stmts);

                /** @var Context $original_context */
                $else_redefined_vars = Context::getRedefinedVars($original_context, $else_context);

                // if it doesn't end in a return
                if (!$has_leaving_statements) {
                    if ($new_vars === null) {
                        $new_vars = array_diff_key($else_context->vars_in_scope, $context->vars_in_scope);
                    } else {
                        foreach ($new_vars as $new_var => $type) {
                            if (!isset($else_context->vars_in_scope[$new_var])) {
                                unset($new_vars[$new_var]);
                            } else {
                                $new_vars[$new_var] = Type::combineUnionTypes(
                                    $type,
                                    $else_context->vars_in_scope[$new_var]
                                );
                            }
                        }
                    }

                    if ($redefined_vars === null) {
                        $redefined_vars = $else_redefined_vars;
                        $possibly_redefined_vars = $redefined_vars;
                    } else {
                        foreach ($redefined_vars as $redefined_var => $type) {
                            if (!isset($else_redefined_vars[$redefined_var])) {
                                unset($redefined_vars[$redefined_var]);
                            } else {
                                $redefined_vars[$redefined_var] = Type::combineUnionTypes(
                                    $else_redefined_vars[$redefined_var],
                                    $type
                                );
                            }
                        }

                        foreach ($else_redefined_vars as $var => $type) {
                            if ($type->isMixed()) {
                                $possibly_redefined_vars[$var] = $type;
                            } elseif (isset($possibly_redefined_vars[$var])) {
                                $possibly_redefined_vars[$var] = Type::combineUnionTypes(
                                    $type,
                                    $possibly_redefined_vars[$var]
                                );
                            } else {
                                $possibly_redefined_vars[$var] = $type;
                            }
                        }
                    }
                }

                // update the parent context as necessary
                if ($negatable_if_types) {
                    $context->update(
                        $old_else_context,
                        $else_context,
                        $has_leaving_statements,
                        array_keys($negatable_if_types),
                        $updated_vars
                    );
                }

                if (!$has_ending_statements) {
                    $vars = array_diff_key($else_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);

                    if ($has_leaving_statements && $loop_context) {
                        if ($redefined_loop_vars === null) {
                            $redefined_loop_vars = $else_redefined_vars;
                            $possibly_redefined_loop_vars = $redefined_loop_vars;
                        } else {
                            foreach ($redefined_loop_vars as $redefined_var => $type) {
                                if (!isset($else_redefined_vars[$redefined_var])) {
                                    unset($redefined_loop_vars[$redefined_var]);
                                } else {
                                    $redefined_loop_vars[$redefined_var] = Type::combineUnionTypes(
                                        $else_redefined_vars[$redefined_var],
                                        $type
                                    );
                                }
                            }

                            foreach ($else_redefined_vars as $var => $type) {
                                if ($type->isMixed()) {
                                    $possibly_redefined_loop_vars[$var] = $type;
                                } elseif (isset($possibly_redefined_loop_vars[$var])) {
                                    $possibly_redefined_loop_vars[$var] = Type::combineUnionTypes(
                                        $type,
                                        $possibly_redefined_loop_vars[$var]
                                    );
                                } else {
                                    $possibly_redefined_loop_vars[$var] = $type;
                                }
                            }
                        }

                        $loop_context->vars_possibly_in_scope = array_merge(
                            $vars,
                            $loop_context->vars_possibly_in_scope
                        );
                    } elseif (!$has_leaving_statements) {
                        $new_vars_possibly_in_scope = array_merge($vars, $new_vars_possibly_in_scope);
                    }
                }
            }
        }

        $context->vars_possibly_in_scope = array_merge($context->vars_possibly_in_scope, $new_vars_possibly_in_scope);

        // vars can only be defined/redefined if there was an else (defined in every block)
        if ($stmt->else) {
            if ($new_vars) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $new_vars);
            }

            if ($redefined_vars) {
                foreach ($redefined_vars as $var => $type) {
                    $context->vars_in_scope[$var] = $type;
                    $updated_vars[$var] = true;
                }
            }

            if ($redefined_loop_vars && $loop_context) {
                foreach ($redefined_loop_vars as $var => $type) {
                    $loop_context->vars_in_scope[$var] = $type;
                    $updated_loop_vars[$var] = true;
                }
            }
        } else {
            if ($forced_new_vars) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $forced_new_vars);
            }
        }

        if ($possibly_redefined_vars) {
            foreach ($possibly_redefined_vars as $var => $type) {
                if (isset($context->vars_in_scope[$var]) && !isset($updated_vars[$var])) {
                    $context->vars_in_scope[$var] = Type::combineUnionTypes($context->vars_in_scope[$var], $type);
                }
            }
        }

        if ($possibly_redefined_loop_vars && $loop_context) {
            foreach ($possibly_redefined_loop_vars as $var => $type) {
                if (isset($loop_context->vars_in_scope[$var]) && !isset($updated_loop_vars[$var])) {
                    $loop_context->vars_in_scope[$var] = Type::combineUnionTypes(
                        $loop_context->vars_in_scope[$var],
                        $type
                    );
                }
            }
        }

        return null;
    }

    /**
     * @param  PhpParser\Node\Expr $stmt
     * @return PhpParser\Node\Expr|null
     */
    protected static function getFirstFunctionCall(PhpParser\Node\Expr $stmt)
    {
        if ($stmt instanceof PhpParser\Node\Expr\MethodCall
            || $stmt instanceof PhpParser\Node\Expr\StaticCall
            || $stmt instanceof PhpParser\Node\Expr\FuncCall
        ) {
            return $stmt;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            return self::getFirstFunctionCall($stmt->left);
        }

        if ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            return self::getFirstFunctionCall($stmt->expr);
        }

        return null;
    }
}
