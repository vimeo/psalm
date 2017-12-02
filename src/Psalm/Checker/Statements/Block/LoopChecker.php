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
use Psalm\IssueBuffer;
use Psalm\Scope\LoopScope;
use Psalm\Type;

class LoopChecker
{
    /**
     * Checks an array of statements in a loop
     *
     * @param  array<PhpParser\Node\Stmt|PhpParser\Node\Expr>   $stmts
     * @param  PhpParser\Node\Expr[]                            $pre_conditions
     * @param  PhpParser\Node\Expr[]                            $post_conditions
     * @param  array<int, string>                               $asserted_vars
     * @param  Context                                          $loop_scope->loop_context
     * @param  Context                                          $loop_scope->loop_parent_context
     *
     * @return false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        array $stmts,
        array $pre_conditions,
        array $post_conditions,
        LoopScope $loop_scope
    ) {
        $traverser = new PhpParser\NodeTraverser;

        $assignment_mapper = new \Psalm\Visitor\AssignmentMapVisitor($loop_scope->loop_context->self);
        $traverser->addVisitor($assignment_mapper);

        $traverser->traverse($stmts);

        $assignment_map = $assignment_mapper->getAssignmentMap();

        $assignment_depth = 0;

        $asserted_vars = [];

        $pre_condition_clauses = [];

        if ($pre_conditions) {
            foreach ($pre_conditions as $pre_condition) {
                $pre_condition_clauses = array_merge(
                    $pre_condition_clauses,
                    AlgebraChecker::getFormula(
                        $pre_condition,
                        $loop_scope->loop_context->self,
                        $statements_checker
                    )
                );
            }
        } else {
            $asserted_vars = Context::getNewOrUpdatedVarIds(
                $loop_scope->loop_parent_context,
                $loop_scope->loop_context
            );
        }

        $final_actions = ScopeChecker::getFinalControlActions($stmts);
        $has_ending_statement = $final_actions === [ScopeChecker::ACTION_END];
        $has_break_statement = $final_actions === [ScopeChecker::ACTION_BREAK];
        $has_continue_statement = $final_actions === [ScopeChecker::ACTION_CONTINUE];

        if ($assignment_map) {
            $first_var_id = array_keys($assignment_map)[0];

            $assignment_depth = self::getAssignmentMapDepth($first_var_id, $assignment_map);
        }

        $loop_scope->loop_context->parent_context = $loop_scope->loop_parent_context;

        if ($assignment_depth === 0 || $has_break_statement) {
            $inner_context = clone $loop_scope->loop_context;

            $inner_context->parent_context = $loop_scope->loop_context;

            foreach ($pre_conditions as $pre_condition) {
                self::applyPreConditionToLoopContext(
                    $statements_checker,
                    $pre_condition,
                    $pre_condition_clauses,
                    $inner_context,
                    $loop_scope->loop_parent_context
                );
            }

            $statements_checker->analyze($stmts, $inner_context, $loop_scope);
            self::updateLoopScopeContexts($loop_scope, $loop_scope->loop_parent_context);

            foreach ($post_conditions as $post_condition) {
                if (ExpressionChecker::analyze(
                    $statements_checker,
                    $post_condition,
                    $loop_scope->loop_context
                ) === false
                ) {
                    return false;
                }
            }

            $loop_scope->loop_parent_context->vars_possibly_in_scope = array_merge(
                $inner_context->vars_possibly_in_scope,
                $loop_scope->loop_parent_context->vars_possibly_in_scope
            );
        } else {
            $pre_outer_context = clone $loop_scope->loop_parent_context;

            IssueBuffer::startRecording();

            foreach ($pre_conditions as $pre_condition) {
                $asserted_vars = array_merge(
                    self::applyPreConditionToLoopContext(
                        $statements_checker,
                        $pre_condition,
                        $pre_condition_clauses,
                        $loop_scope->loop_context,
                        $loop_scope->loop_parent_context
                    ),
                    $asserted_vars
                );
            }

            // record all the vars that existed before we did the first pass through the loop
            $pre_loop_context = clone $loop_scope->loop_context;

            $inner_context = clone $loop_scope->loop_context;
            $inner_context->parent_context = $loop_scope->loop_context;

            $asserted_vars = array_unique($asserted_vars);

            $statements_checker->analyze($stmts, $inner_context, $loop_scope);
            self::updateLoopScopeContexts($loop_scope, $pre_outer_context);

            foreach ($post_conditions as $post_condition) {
                if (ExpressionChecker::analyze($statements_checker, $post_condition, $inner_context) === false) {
                    return false;
                }
            }

            $recorded_issues = IssueBuffer::clearRecordingLevel();
            IssueBuffer::stopRecording();

            for ($i = 0; $i < $assignment_depth; ++$i) {
                $vars_to_remove = [];

                $has_changes = false;

                // reset the $inner_context to what it was before we started the analysis,
                // but union the types with what's in the loop scope

                foreach ($inner_context->vars_in_scope as $var_id => $type) {
                    if (in_array($var_id, $asserted_vars, true)) {
                        // set the vars to whatever the while/foreach loop expects them to be
                        if (!isset($pre_loop_context->vars_in_scope[$var_id])
                            || (string)$type !== (string)$pre_loop_context->vars_in_scope[$var_id]
                        ) {
                            $inner_context->vars_in_scope[$var_id] = $pre_loop_context->vars_in_scope[$var_id];
                            $has_changes = true;
                        }
                    } elseif (isset($pre_outer_context->vars_in_scope[$var_id])) {
                        $str_type = (string)$type;

                        if ($str_type !== (string)$pre_outer_context->vars_in_scope[$var_id]) {
                            $has_changes = true;

                            // widen the foreach context type with the initial context type
                            $inner_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                                $inner_context->vars_in_scope[$var_id],
                                $pre_outer_context->vars_in_scope[$var_id]
                            );

                            // if there's a change, invalidate related clauses
                            $pre_loop_context->removeVarFromConflictingClauses($var_id);
                        }

                        if ($str_type !== (string)$loop_scope->loop_context->vars_in_scope[$var_id]) {
                            $has_changes = true;

                            // widen the foreach context type with the initial context type
                            $inner_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                                $inner_context->vars_in_scope[$var_id],
                                $loop_scope->loop_context->vars_in_scope[$var_id]
                            );

                            // if there's a change, invalidate related clauses
                            $pre_loop_context->removeVarFromConflictingClauses($var_id);
                        }
                    } else {
                        $vars_to_remove[] = $var_id;
                    }
                }

                foreach ($asserted_vars as $var_id) {
                    if (!isset($inner_context->vars_in_scope[$var_id])) {
                        $inner_context->vars_in_scope[$var_id] = $pre_loop_context->vars_in_scope[$var_id];
                    }
                }

                $loop_scope->loop_parent_context->vars_possibly_in_scope = array_merge(
                    $inner_context->vars_possibly_in_scope,
                    $loop_scope->loop_parent_context->vars_possibly_in_scope
                );

                // if there are no changes to the types, no need to re-examine
                if (!$has_changes) {
                    break;
                }

                // remove vars that were defined in the foreach
                foreach ($vars_to_remove as $var_id) {
                    unset($inner_context->vars_in_scope[$var_id]);
                }

                $inner_context->clauses = $pre_loop_context->clauses;

                IssueBuffer::startRecording();

                foreach ($pre_conditions as $pre_condition) {
                    self::applyPreConditionToLoopContext(
                        $statements_checker,
                        $pre_condition,
                        $pre_condition_clauses,
                        $inner_context,
                        $loop_scope->loop_parent_context
                    );
                }

                $statements_checker->analyze($stmts, $inner_context, $loop_scope);

                self::updateLoopScopeContexts($loop_scope, $pre_outer_context);

                foreach ($post_conditions as $post_condition) {
                    if (ExpressionChecker::analyze($statements_checker, $post_condition, $inner_context) === false) {
                        return false;
                    }
                }

                $recorded_issues = IssueBuffer::clearRecordingLevel();

                IssueBuffer::stopRecording();
            }

            if ($recorded_issues) {
                foreach ($recorded_issues as $recorded_issue) {
                    // if we're not in any loops then this will just result in the issue being emitted
                    IssueBuffer::bubbleUp($recorded_issue);
                }
            }
        }

        $does_sometimes_break = in_array(ScopeChecker::ACTION_BREAK, $loop_scope->final_actions, true);
        $does_always_break = $loop_scope->final_actions === [ScopeChecker::ACTION_BREAK];

        if ($does_sometimes_break) {
            if ($loop_scope->possibly_redefined_loop_parent_vars !== null) {
                foreach ($loop_scope->possibly_redefined_loop_parent_vars as $var => $type) {
                    $loop_scope->loop_parent_context->vars_in_scope[$var] = Type::combineUnionTypes(
                        $type,
                        $loop_scope->loop_parent_context->vars_in_scope[$var]
                    );
                }
            }
        }

        if (!$does_always_break) {
            foreach ($loop_scope->loop_parent_context->vars_in_scope as $var_id => $type) {
                if ($type->isMixed()) {
                    continue;
                }

                if (!isset($inner_context->vars_in_scope[$var_id])) {
                    unset($loop_scope->loop_parent_context->vars_in_scope[$var_id]);
                    continue;
                }

                if ($inner_context->vars_in_scope[$var_id]->isMixed()) {
                    $loop_scope->loop_parent_context->vars_in_scope[$var_id] =
                        $inner_context->vars_in_scope[$var_id];
                    $loop_scope->loop_parent_context->removeVarFromConflictingClauses($var_id);
                    continue;
                }

                if ((string) $inner_context->vars_in_scope[$var_id] !== (string) $type) {
                    $loop_scope->loop_parent_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $loop_scope->loop_parent_context->vars_in_scope[$var_id],
                        $inner_context->vars_in_scope[$var_id]
                    );

                    $loop_scope->loop_parent_context->removeVarFromConflictingClauses($var_id);
                }
            }
        }

        if ($pre_conditions && $pre_condition_clauses && !ScopeChecker::doesEverBreak($stmts)) {
            // if the loop contains an assertion and there are no break statements, we can negate that assertion
            // and apply it to the current context
            $negated_pre_condition_types = AlgebraChecker::getTruthsFromFormula(
                AlgebraChecker::negateFormula($pre_condition_clauses)
            );

            if ($negated_pre_condition_types) {
                $changed_var_ids = [];

                $vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes(
                    $negated_pre_condition_types,
                    $inner_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_checker,
                    new CodeLocation($statements_checker->getSource(), $pre_conditions[0]),
                    $statements_checker->getSuppressedIssues()
                );

                if ($vars_in_scope_reconciled === false) {
                    return false;
                }

                foreach ($loop_scope->loop_parent_context->vars_in_scope as $var_id => $type) {
                    if (isset($vars_in_scope_reconciled[$var_id])) {
                        $loop_scope->loop_parent_context->vars_in_scope[$var_id] = $vars_in_scope_reconciled[$var_id];
                    }
                }

                foreach ($changed_var_ids as $changed_var_id) {
                    $loop_scope->loop_parent_context->removeVarFromConflictingClauses($changed_var_id);
                }
            }
        }
    }

    /**
     * @param  LoopScope $loop_scope
     * @param  Context   $pre_outer_context
     *
     * @return void
     */
    private static function updateLoopScopeContexts(
        LoopScope $loop_scope,
        Context $pre_outer_context
    ) {
        $updated_loop_vars = [];

        if (!in_array(ScopeChecker::ACTION_CONTINUE, $loop_scope->final_actions, true)) {
            $loop_scope->loop_context->vars_in_scope = $pre_outer_context->vars_in_scope;
        } else {
            if ($loop_scope->redefined_loop_vars !== null) {
                foreach ($loop_scope->redefined_loop_vars as $var => $type) {
                    $loop_scope->loop_context->vars_in_scope[$var] = $type;
                    $updated_loop_vars[$var] = true;
                }
            }

            if ($loop_scope->possibly_redefined_loop_vars) {
                foreach ($loop_scope->possibly_redefined_loop_vars as $var => $type) {
                    if ($loop_scope->loop_context->hasVariable($var) && !isset($updated_loop_vars[$var])) {
                        $loop_scope->loop_context->vars_in_scope[$var] = Type::combineUnionTypes(
                            $loop_scope->loop_context->vars_in_scope[$var],
                            $type
                        );
                    }
                }
            }
        }

        // merge vars possibly in scope at the end of each loop
        $loop_scope->loop_context->vars_possibly_in_scope = array_merge(
            $loop_scope->loop_context->vars_possibly_in_scope,
            $loop_scope->vars_possibly_in_scope
        );
    }

    /**
     * @param  PhpParser\Node\Expr $pre_condition
     * @param  array<int, Clause>  $pre_condition_clauses
     * @param  Context             $loop_context
     * @param  Context             $outer_context
     *
     * @return string[]
     */
    private static function applyPreConditionToLoopContext(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $pre_condition,
        array $pre_condition_clauses,
        Context $loop_context,
        Context $outer_context
    ) {
        $pre_referenced_var_ids = $loop_context->referenced_var_ids;
        $loop_context->referenced_var_ids = [];

        $loop_context->inside_conditional = true;

        if (ExpressionChecker::analyze($statements_checker, $pre_condition, $loop_context) === false) {
            return [];
        }

        $loop_context->inside_conditional = false;

        $new_referenced_var_ids = $loop_context->referenced_var_ids;
        $loop_context->referenced_var_ids = array_merge($pre_referenced_var_ids, $new_referenced_var_ids);

        $asserted_vars = Context::getNewOrUpdatedVarIds($outer_context, $loop_context);

        $loop_context->clauses = AlgebraChecker::simplifyCNF(
            array_merge($outer_context->clauses, $pre_condition_clauses)
        );

        $reconcilable_while_types = AlgebraChecker::getTruthsFromFormula($loop_context->clauses);

        // if the while has an or as the main component, we cannot safely reason about it
        if ($pre_condition instanceof PhpParser\Node\Expr\BinaryOp &&
            ($pre_condition instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr ||
                $pre_condition instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr)
        ) {
            // do nothing
        } else {
            $changed_var_ids = [];

            $pre_condition_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes(
                $reconcilable_while_types,
                $loop_context->vars_in_scope,
                $changed_var_ids,
                $new_referenced_var_ids,
                $statements_checker,
                new CodeLocation($statements_checker->getSource(), $pre_condition),
                $statements_checker->getSuppressedIssues()
            );

            $loop_context->vars_in_scope = $pre_condition_vars_in_scope_reconciled;
        }

        return $asserted_vars;
    }

    /**
     * @param  string                               $first_var_id
     * @param  array<string, array<string, bool>>   $assignment_map
     *
     * @return int
     */
    private static function getAssignmentMapDepth($first_var_id, array $assignment_map)
    {
        $max_depth = 0;

        $assignment_var_ids = $assignment_map[$first_var_id];
        unset($assignment_map[$first_var_id]);

        foreach ($assignment_var_ids as $assignment_var_id => $_) {
            $depth = 1;

            if (isset($assignment_map[$assignment_var_id])) {
                $depth = 1 + self::getAssignmentMapDepth($assignment_var_id, $assignment_map);
            }

            if ($depth > $max_depth) {
                $max_depth = $depth;
            }
        }

        return $max_depth;
    }
}
