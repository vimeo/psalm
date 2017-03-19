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
use Psalm\IfScope;
use Psalm\IssueBuffer;
use Psalm\Issue\ConflictingReferenceConstraint;
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
    public static function analyze(
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
        $first_if_cond_expr = self::getDefinitelyEvaluatedExpression($stmt->cond);

        $context->inside_conditional = true;

        if ($first_if_cond_expr &&
            ExpressionChecker::analyze($statements_checker, $first_if_cond_expr, $context) === false
        ) {
            return false;
        }

        $context->inside_conditional = false;

        $if_scope = new IfScope();

        $if_scope->loop_context = $loop_context;
        $if_scope->has_elseifs = count($stmt->elseifs) > 0;

        $if_context = clone $context;

        $if_context->parent_context = $context;

        // we need to clone the current context so our ongoing updates to $context don't mess with elseif/else blocks
        $original_context = clone $context;

        $if_context->inside_conditional = true;

        if ($first_if_cond_expr !== $stmt->cond &&
            ExpressionChecker::analyze($statements_checker, $stmt->cond, $if_context) === false
        ) {
            return false;
        }

        $if_context->inside_conditional = false;

        $reconcilable_if_types = null;

        $if_clauses = AlgebraChecker::getFormula(
            $stmt->cond,
            $context->self,
            $statements_checker
        );

        $if_context->clauses = AlgebraChecker::simplifyCNF(array_merge($context->clauses, $if_clauses));

        $if_scope->negated_clauses = AlgebraChecker::negateFormula($if_clauses);

        $if_scope->negated_types = AlgebraChecker::getTruthsFromFormula($if_scope->negated_clauses);

        $reconcilable_if_types = AlgebraChecker::getTruthsFromFormula($if_context->clauses);

        // if the if has an || in the conditional, we cannot easily reason about it
        if ($reconcilable_if_types) {
            $changed_vars = [];

            $if_vars_in_scope_reconciled =
                TypeChecker::reconcileKeyedTypes(
                    $reconcilable_if_types,
                    $if_context->vars_in_scope,
                    $changed_vars,
                    $statements_checker->getFileChecker(),
                    new CodeLocation($statements_checker->getSource(), $stmt->cond),
                    $statements_checker->getSuppressedIssues()
                );

            if ($if_vars_in_scope_reconciled === false) {
                return false;
            }

            foreach ($changed_vars as $changed_var) {
                //$if_context->removeVarFromClauses($changed_var);
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

        if ($context->collect_references) {
            $context->referenced_vars = array_merge(
                $if_context->referenced_vars,
                $context->referenced_vars
            );
        }

        $temp_else_context = clone $original_context;

        if ($if_scope->negated_types) {
            $changed_vars = [];

            $else_vars_reconciled = TypeChecker::reconcileKeyedTypes(
                $if_scope->negated_types,
                $temp_else_context->vars_in_scope,
                $changed_vars,
                $statements_checker->getFileChecker(),
                new CodeLocation($statements_checker->getSource(), $stmt->cond),
                $statements_checker->getSuppressedIssues()
            );

            if ($else_vars_reconciled === false) {
                return false;
            }

            $temp_else_context->vars_in_scope = $else_vars_reconciled;
        }

        // we calculate the vars redefined in a hypothetical else statement to determine
        // which vars of the if we can safely change
        $pre_assignment_else_redefined_vars = $temp_else_context->getRedefinedVars($context);

        // check the if
        self::analyzeIfBlock(
            $statements_checker,
            $stmt,
            $if_scope,
            $if_context,
            $old_if_context,
            $context,
            $pre_assignment_else_redefined_vars
        );

        // check the elseifs
        foreach ($stmt->elseifs as $elseif) {
            $elseif_context = clone $original_context;

            self::analyzeElseIfBlock(
                $statements_checker,
                $elseif,
                $if_scope,
                $elseif_context,
                $context
            );
        }

        // check the else
        if ($stmt->else) {
            $else_context = clone $original_context;

            self::analyzeElseBlock(
                $statements_checker,
                $stmt->else,
                $if_scope,
                $else_context,
                $context
            );
        }

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $if_scope->new_vars_possibly_in_scope
        );

        $updated_loop_vars = [];

        // vars can only be defined/redefined if there was an else (defined in every block)
        if ($stmt->else) {
            if ($if_scope->new_vars) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $if_scope->new_vars);
            }

            if ($if_scope->redefined_vars) {
                foreach ($if_scope->redefined_vars as $var => $type) {
                    $context->vars_in_scope[$var] = $type;
                    $if_scope->updated_vars[$var] = true;
                }
            }

            if ($if_scope->redefined_loop_vars && $loop_context) {
                foreach ($if_scope->redefined_loop_vars as $var => $type) {
                    $loop_context->vars_in_scope[$var] = $type;
                    $updated_loop_vars[$var] = true;
                }
            }
        } else {
            if ($if_scope->forced_new_vars) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $if_scope->forced_new_vars);
            }
        }

        if ($if_scope->possibly_redefined_vars) {
            foreach ($if_scope->possibly_redefined_vars as $var => $type) {
                if (!$type->failed_reconciliation &&
                    $context->hasVariable($var) &&
                    !isset($if_scope->updated_vars[$var])
                ) {
                    $context->vars_in_scope[$var] = Type::combineUnionTypes($context->vars_in_scope[$var], $type);
                }
            }
        }

        if ($if_scope->possibly_redefined_loop_vars && $loop_context) {
            foreach ($if_scope->possibly_redefined_loop_vars as $var => $type) {
                if ($loop_context->hasVariable($var) && !isset($updated_loop_vars[$var])) {
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
     * @param  StatementsChecker        $statements_checker
     * @param  PhpParser\Node\Stmt\If_  $stmt
     * @param  IfScope                  $if_scope
     * @param  Context                  $if_context
     * @param  Context                  $old_if_context
     * @param  Context                  $outer_context
     * @param  array<string,Type\Union> $pre_assignment_else_redefined_vars
     * @return false|null
     */
    protected static function analyzeIfBlock(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\If_ $stmt,
        IfScope $if_scope,
        Context $if_context,
        Context $old_if_context,
        Context $outer_context,
        array $pre_assignment_else_redefined_vars
    ) {
        $has_ending_statements = ScopeChecker::doesAlwaysReturnOrThrow($stmt->stmts);

        $has_leaving_statements = $has_ending_statements || ScopeChecker::doesAlwaysBreakOrContinue($stmt->stmts);

        if ($statements_checker->analyze($stmt->stmts, $if_context, $if_scope->loop_context) === false) {
            return false;
        }

        if ($if_context->byref_constraints !== null) {
            foreach ($if_context->byref_constraints as $var_id => $byref_constraint) {
                if ($outer_context->byref_constraints !== null &&
                    isset($outer_context->byref_constraints[$var_id]) &&
                    !TypeChecker::isContainedBy(
                        $byref_constraint->type,
                        $outer_context->byref_constraints[$var_id]->type,
                        $statements_checker->getFileChecker()
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new ConflictingReferenceConstraint(
                            'There is more than one pass-by--reference constraint on ' . $var_id,
                            new CodeLocation($statements_checker, $stmt, true)
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
            $outer_context->referenced_vars = array_merge(
                $outer_context->referenced_vars,
                $if_context->referenced_vars
            );
        }

        $mic_drop = false;

        if (!$has_leaving_statements) {
            $if_scope->new_vars = array_diff_key($if_context->vars_in_scope, $outer_context->vars_in_scope);

            // if we have a check like if (!isset($a)) { $a = true; } we want to make sure $a is always set
            foreach ($if_scope->new_vars as $var_id => $_) {
                if (isset($if_scope->negated_types[$var_id]) && $if_scope->negated_types[$var_id] === 'isset') {
                    $if_scope->forced_new_vars[$var_id] = Type::getMixed();
                }
            }

            $if_scope->redefined_vars = $if_context->getRedefinedVars($outer_context);
            $if_scope->possibly_redefined_vars = $if_scope->redefined_vars;
            $if_scope->reasonable_clauses = $if_context->clauses;
        } elseif (!$stmt->else && !$stmt->elseifs) {
            if ($if_scope->negated_types) {
                $changed_vars = [];

                $outer_context_vars_reconciled = TypeChecker::reconcileKeyedTypes(
                    $if_scope->negated_types,
                    $outer_context->vars_in_scope,
                    $changed_vars,
                    $statements_checker->getFileChecker(),
                    new CodeLocation($statements_checker->getSource(), $stmt->cond),
                    $statements_checker->getSuppressedIssues()
                );

                foreach ($changed_vars as $changed_var) {
                    $outer_context->removeVarFromClauses($changed_var);
                }

                if ($outer_context_vars_reconciled === false) {
                    return false;
                }

                $outer_context->vars_in_scope = $outer_context_vars_reconciled;
                $mic_drop = true;
            }

            $outer_context->clauses = AlgebraChecker::simplifyCNF(
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

            $outer_context->update(
                $old_if_context,
                $if_context,
                $has_leaving_statements,
                $vars_to_update,
                $if_scope->updated_vars
            );
        }

        if (!$has_ending_statements) {
            $vars = array_diff_key($if_context->vars_possibly_in_scope, $outer_context->vars_possibly_in_scope);

            if ($has_leaving_statements && $if_scope->loop_context) {
                $if_scope->redefined_loop_vars = $if_context->getRedefinedVars($if_scope->loop_context);
                $if_scope->possibly_redefined_loop_vars = $if_scope->redefined_loop_vars;
            }

            // if we're leaving this block, add vars to outer for loop scope
            if ($has_leaving_statements) {
                if ($if_scope->loop_context) {
                    $if_scope->loop_context->vars_possibly_in_scope = array_merge(
                        $if_scope->loop_context->vars_possibly_in_scope,
                        $vars
                    );
                }
            } else {
                $if_scope->new_vars_possibly_in_scope = $vars;
            }
        }
    }

    /**
     * @param  StatementsChecker           $statements_checker
     * @param  PhpParser\Node\Stmt\ElseIf_ $elseif
     * @param  IfScope                     $if_scope
     * @param  Context                     $elseif_context
     * @param  Context                     $outer_context
     * @return false|null
     */
    protected static function analyzeElseIfBlock(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\ElseIf_ $elseif,
        IfScope $if_scope,
        Context $elseif_context,
        Context $outer_context
    ) {
        $original_context = clone $elseif_context;

        if ($if_scope->negated_types) {
            $changed_vars = [];

            $elseif_vars_reconciled = TypeChecker::reconcileKeyedTypes(
                $if_scope->negated_types,
                $elseif_context->vars_in_scope,
                $changed_vars,
                $statements_checker->getFileChecker(),
                new CodeLocation($statements_checker->getSource(), $elseif->cond),
                $statements_checker->getSuppressedIssues()
            );

            if ($elseif_vars_reconciled === false) {
                return false;
            }

            $elseif_context->vars_in_scope = $elseif_vars_reconciled;
        }

        $pre_conditional_context = clone $elseif_context;

        $elseif_context->inside_conditional = true;

        // check the elseif
        if (ExpressionChecker::analyze($statements_checker, $elseif->cond, $elseif_context) === false) {
            return false;
        }

        $elseif_context->inside_conditional = false;

        $elseif_clauses = AlgebraChecker::getFormula(
            $elseif->cond,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $elseif_context->clauses = AlgebraChecker::simplifyCNF(
            array_merge(
                $original_context->clauses,
                $if_scope->negated_clauses,
                $elseif_clauses
            )
        );

        $reconcilable_elseif_types = AlgebraChecker::getTruthsFromFormula($elseif_context->clauses);
        $negated_elseif_types = AlgebraChecker::getTruthsFromFormula(AlgebraChecker::negateFormula($elseif_clauses));

        $all_negated_vars = array_unique(
            array_merge(
                array_keys($negated_elseif_types),
                array_keys($if_scope->negated_types)
            )
        );

        foreach ($all_negated_vars as $var_id) {
            if (isset($negated_elseif_types[$var_id])) {
                if (isset($if_scope->negated_types[$var_id])) {
                    $if_scope->negated_types[$var_id] = $if_scope->negated_types[$var_id] . '&' . $negated_elseif_types[$var_id];
                } else {
                    $if_scope->negated_types[$var_id] = $negated_elseif_types[$var_id];
                }
            }
        }

        // if the elseif has an || in the conditional, we cannot easily reason about it
        if ($reconcilable_elseif_types) {
            $changed_vars = [];

            $elseif_vars_reconciled = TypeChecker::reconcileKeyedTypes(
                $reconcilable_elseif_types,
                $elseif_context->vars_in_scope,
                $changed_vars,
                $statements_checker->getFileChecker(),
                new CodeLocation($statements_checker->getSource(), $elseif->cond),
                $statements_checker->getSuppressedIssues()
            );

            if ($elseif_vars_reconciled === false) {
                return false;
            }

            $elseif_context->vars_in_scope = $elseif_vars_reconciled;
        }

        $old_elseif_context = clone $elseif_context;

        if ($statements_checker->analyze($elseif->stmts, $elseif_context, $if_scope->loop_context) === false) {
            return false;
        }

        if ($elseif_context->byref_constraints !== null) {
            foreach ($elseif_context->byref_constraints as $var_id => $byref_constraint) {
                if ($outer_context->byref_constraints !== null &&
                    isset($outer_context->byref_constraints[$var_id]) &&
                    !TypeChecker::isContainedBy(
                        $byref_constraint->type,
                        $outer_context->byref_constraints[$var_id]->type,
                        $statements_checker->getFileChecker()
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new ConflictingReferenceConstraint(
                            'There is more than one pass-by--reference constraint on ' . $var_id,
                            new CodeLocation($statements_checker, $elseif, true)
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

        if (count($elseif->stmts)) {
            // has a return/throw at end
            $has_ending_statements = ScopeChecker::doesAlwaysReturnOrThrow($elseif->stmts);

            $has_leaving_statements = $has_ending_statements ||
                ScopeChecker::doesAlwaysBreakOrContinue($elseif->stmts);

            // update the parent context as necessary
            $elseif_redefined_vars = $elseif_context->getRedefinedVars($original_context);

            if (!$has_leaving_statements) {
                if ($if_scope->new_vars === null) {
                    $if_scope->new_vars = array_diff_key($elseif_context->vars_in_scope, $outer_context->vars_in_scope);
                } else {
                    foreach ($if_scope->new_vars as $new_var => $type) {
                        if (!$elseif_context->hasVariable($new_var)) {
                            unset($if_scope->new_vars[$new_var]);
                        } else {
                            $if_scope->new_vars[$new_var] = Type::combineUnionTypes(
                                $type,
                                $elseif_context->vars_in_scope[$new_var]
                            );
                        }
                    }
                }

                if ($if_scope->redefined_vars === null) {
                    $if_scope->redefined_vars = $elseif_redefined_vars;
                    $if_scope->possibly_redefined_vars = $if_scope->redefined_vars;
                } else {
                    foreach ($if_scope->redefined_vars as $redefined_var => $type) {
                        if (!isset($elseif_redefined_vars[$redefined_var])) {
                            unset($if_scope->redefined_vars[$redefined_var]);
                        } else {
                            $if_scope->redefined_vars[$redefined_var] = Type::combineUnionTypes(
                                $elseif_redefined_vars[$redefined_var],
                                $type
                            );
                        }
                    }

                    foreach ($elseif_redefined_vars as $var => $type) {
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
                    $if_scope->reasonable_clauses = AlgebraChecker::combineOredClauses(
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
                    $changed_vars = [];

                    $leaving_vars_reconciled = TypeChecker::reconcileKeyedTypes(
                        $negated_elseif_types,
                        $pre_conditional_context->vars_in_scope,
                        $changed_vars,
                        $statements_checker->getFileChecker(),
                        new CodeLocation($statements_checker->getSource(), $elseif),
                        $statements_checker->getSuppressedIssues()
                    );

                    if ($leaving_vars_reconciled === false) {
                        return false;
                    }

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
                    $negated_keys = [];

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
                $vars = array_diff_key($elseif_context->vars_possibly_in_scope, $outer_context->vars_possibly_in_scope);

                // if we're leaving this block, add vars to outer for loop scope
                if ($has_leaving_statements && $if_scope->loop_context) {
                    if ($if_scope->redefined_loop_vars === null) {
                        $if_scope->redefined_loop_vars = $elseif_redefined_vars;
                        $if_scope->possibly_redefined_loop_vars = $if_scope->redefined_loop_vars;
                    } else {
                        foreach ($if_scope->redefined_loop_vars as $redefined_var => $type) {
                            if (!isset($elseif_redefined_vars[$redefined_var])) {
                                unset($if_scope->redefined_loop_vars[$redefined_var]);
                            } else {
                                $if_scope->redefined_loop_vars[$redefined_var] = Type::combineUnionTypes(
                                    $elseif_redefined_vars[$redefined_var],
                                    $type
                                );
                            }
                        }

                        foreach ($elseif_redefined_vars as $var => $type) {
                            if ($type->isMixed()) {
                                $if_scope->possibly_redefined_loop_vars[$var] = $type;
                            } elseif (isset($if_scope->possibly_redefined_loop_vars[$var])) {
                                $if_scope->possibly_redefined_loop_vars[$var] = Type::combineUnionTypes(
                                    $type,
                                    $if_scope->possibly_redefined_loop_vars[$var]
                                );
                            } else {
                                $if_scope->possibly_redefined_loop_vars[$var] = $type;
                            }
                        }
                    }

                    $if_scope->loop_context->vars_possibly_in_scope = array_merge(
                        $vars,
                        $if_scope->loop_context->vars_possibly_in_scope
                    );
                } elseif (!$has_leaving_statements) {
                    $if_scope->new_vars_possibly_in_scope = array_merge($vars, $if_scope->new_vars_possibly_in_scope);
                }
            }
        }

        if ($outer_context->collect_references) {
            $outer_context->referenced_vars = array_merge(
                $outer_context->referenced_vars,
                $elseif_context->referenced_vars
            );
        }

        $if_scope->negated_clauses = array_merge(
            $if_scope->negated_clauses,
            AlgebraChecker::negateFormula($elseif_clauses)
        );
    }

    /**
     * @param  StatementsChecker         $statements_checker
     * @param  PhpParser\Node\Stmt\Else_ $else
     * @param  IfScope                   $if_scope
     * @param  Context                   $else_context
     * @param  Context                   $outer_context
     * @return false|null
     */
    protected static function analyzeElseBlock(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\Else_ $else,
        IfScope $if_scope,
        Context $else_context,
        Context $outer_context
    ) {
        $original_context = clone $else_context;

        $else_context->clauses = AlgebraChecker::simplifyCNF(
            array_merge(
                $outer_context->clauses,
                $if_scope->negated_clauses
            )
        );

        $else_types = AlgebraChecker::getTruthsFromFormula($else_context->clauses);

        if ($else_types) {
            $changed_vars = [];

            $else_vars_reconciled = TypeChecker::reconcileKeyedTypes(
                $else_types,
                $else_context->vars_in_scope,
                $changed_vars,
                $statements_checker->getFileChecker(),
                new CodeLocation($statements_checker->getSource(), $else),
                $statements_checker->getSuppressedIssues()
            );

            if ($else_vars_reconciled === false) {
                return false;
            }

            $else_context->vars_in_scope = $else_vars_reconciled;
        }

        $old_else_context = clone $else_context;

        if ($statements_checker->analyze($else->stmts, $else_context, $if_scope->loop_context) === false) {
            return false;
        }

        if ($else_context->byref_constraints !== null) {
            foreach ($else_context->byref_constraints as $var_id => $byref_constraint) {
                if ($outer_context->byref_constraints !== null &&
                    isset($outer_context->byref_constraints[$var_id]) &&
                    !TypeChecker::isContainedBy(
                        $byref_constraint->type,
                        $outer_context->byref_constraints[$var_id]->type,
                        $statements_checker->getFileChecker()
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new ConflictingReferenceConstraint(
                            'There is more than one pass-by--reference constraint on ' . $var_id,
                            new CodeLocation($statements_checker, $else, true)
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
            $outer_context->referenced_vars = array_merge(
                $outer_context->referenced_vars,
                $else_context->referenced_vars
            );
        }

        if (count($else->stmts)) {
            // has a return/throw at end
            $has_ending_statements = ScopeChecker::doesAlwaysReturnOrThrow($else->stmts);

            $has_leaving_statements = $has_ending_statements ||
                ScopeChecker::doesAlwaysBreakOrContinue($else->stmts);

            $else_redefined_vars = $else_context->getRedefinedVars($original_context);

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
            } elseif ($if_scope->reasonable_clauses) {
                $outer_context->clauses = AlgebraChecker::simplifyCNF(
                    array_merge(
                        $if_scope->reasonable_clauses,
                        $original_context->clauses
                    )
                );
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
                $vars = array_diff_key($else_context->vars_possibly_in_scope, $outer_context->vars_possibly_in_scope);

                if ($has_leaving_statements && $if_scope->loop_context) {
                    if ($if_scope->redefined_loop_vars === null) {
                        $if_scope->redefined_loop_vars = $else_redefined_vars;
                        $if_scope->possibly_redefined_loop_vars = $if_scope->redefined_loop_vars;
                    } else {
                        foreach ($if_scope->redefined_loop_vars as $redefined_var => $type) {
                            if (!isset($else_redefined_vars[$redefined_var])) {
                                unset($if_scope->redefined_loop_vars[$redefined_var]);
                            } else {
                                $if_scope->redefined_loop_vars[$redefined_var] = Type::combineUnionTypes(
                                    $else_redefined_vars[$redefined_var],
                                    $type
                                );
                            }
                        }

                        foreach ($else_redefined_vars as $var => $type) {
                            if ($type->isMixed()) {
                                $if_scope->possibly_redefined_loop_vars[$var] = $type;
                            } elseif (isset($if_scope->possibly_redefined_loop_vars[$var])) {
                                $if_scope->possibly_redefined_loop_vars[$var] = Type::combineUnionTypes(
                                    $type,
                                    $if_scope->possibly_redefined_loop_vars[$var]
                                );
                            } else {
                                $if_scope->possibly_redefined_loop_vars[$var] = $type;
                            }
                        }
                    }

                    $if_scope->loop_context->vars_possibly_in_scope = array_merge(
                        $vars,
                        $if_scope->loop_context->vars_possibly_in_scope
                    );
                } elseif (!$has_leaving_statements) {
                    $if_scope->new_vars_possibly_in_scope = array_merge($vars, $if_scope->new_vars_possibly_in_scope);
                }
            }
        }
    }

    /**
     * @param  PhpParser\Node\Expr $stmt
     * @return PhpParser\Node\Expr|null
     */
    protected static function getDefinitelyEvaluatedExpression(PhpParser\Node\Expr $stmt)
    {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalXor
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
