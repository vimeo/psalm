<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\AlgebraChecker;
use Psalm\Checker\ScopeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Clause;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Scope\LoopScope;
use Psalm\Type;
use Psalm\Type\Reconciler;

class LoopChecker
{
    /**
     * Checks an array of statements in a loop
     *
     * @param  array<PhpParser\Node\Stmt>   $stmts
     * @param  PhpParser\Node\Expr[]        $pre_conditions
     * @param  PhpParser\Node\Expr[]        $post_expressions
     * @param  Context                      loop_scope->loop_context
     * @param  Context                      $loop_scope->loop_parent_context
     * @param  bool                         $is_do
     *
     * @return false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        array $stmts,
        array $pre_conditions,
        array $post_expressions,
        LoopScope $loop_scope,
        Context &$inner_context = null,
        $is_do = false
    ) {
        $traverser = new PhpParser\NodeTraverser;

        $assignment_mapper = new \Psalm\Visitor\AssignmentMapVisitor($loop_scope->loop_context->self);
        $traverser->addVisitor($assignment_mapper);

        $traverser->traverse(array_merge($stmts, $post_expressions));

        $assignment_map = $assignment_mapper->getAssignmentMap();

        $assignment_depth = 0;

        $asserted_var_ids = [];

        $pre_condition_clauses = [];

        $original_protected_var_ids = $loop_scope->loop_parent_context->protected_var_ids;

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
            $asserted_var_ids = Context::getNewOrUpdatedVarIds(
                $loop_scope->loop_parent_context,
                $loop_scope->loop_context
            );
        }

        $final_actions = ScopeChecker::getFinalControlActions($stmts);
        $has_break_statement = $final_actions === [ScopeChecker::ACTION_BREAK];

        if ($assignment_map) {
            $first_var_id = array_keys($assignment_map)[0];

            $assignment_depth = self::getAssignmentMapDepth($first_var_id, $assignment_map);
        }

        $loop_scope->loop_context->parent_context = $loop_scope->loop_parent_context;

        if ($assignment_depth === 0 || $has_break_statement) {
            $inner_context = clone $loop_scope->loop_context;

            $inner_context->parent_context = $loop_scope->loop_context;

            if (!$is_do) {
                foreach ($pre_conditions as $pre_condition) {
                    self::applyPreConditionToLoopContext(
                        $statements_checker,
                        $pre_condition,
                        $pre_condition_clauses,
                        $inner_context,
                        $loop_scope->loop_parent_context
                    );
                }
            }

            $inner_context->protected_var_ids = $loop_scope->protected_var_ids;

            $statements_checker->analyze($stmts, $inner_context, $loop_scope);
            self::updateLoopScopeContexts($loop_scope, $loop_scope->loop_parent_context);

            foreach ($post_expressions as $post_expression) {
                if (ExpressionChecker::analyze(
                    $statements_checker,
                    $post_expression,
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

            $analyzer = $statements_checker->getFileChecker()->project_checker->codebase->analyzer;

            $original_mixed_counts = $analyzer->getMixedCountsForFile($statements_checker->getFilePath());

            IssueBuffer::startRecording();

            if (!$is_do) {
                foreach ($pre_conditions as $pre_condition) {
                    $asserted_var_ids = array_merge(
                        self::applyPreConditionToLoopContext(
                            $statements_checker,
                            $pre_condition,
                            $pre_condition_clauses,
                            $loop_scope->loop_context,
                            $loop_scope->loop_parent_context
                        ),
                        $asserted_var_ids
                    );
                }
            }

            // record all the vars that existed before we did the first pass through the loop
            $pre_loop_context = clone $loop_scope->loop_context;

            $inner_context = clone $loop_scope->loop_context;
            $inner_context->parent_context = $loop_scope->loop_context;

            $asserted_var_ids = array_unique($asserted_var_ids);

            $inner_context->protected_var_ids = $loop_scope->protected_var_ids;

            $statements_checker->analyze($stmts, $inner_context, $loop_scope);
            self::updateLoopScopeContexts($loop_scope, $pre_outer_context);

            $inner_context->protected_var_ids = $original_protected_var_ids;

            foreach ($post_expressions as $post_expression) {
                if (ExpressionChecker::analyze($statements_checker, $post_expression, $inner_context) === false) {
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
                    if (in_array($var_id, $asserted_var_ids, true)) {
                        // set the vars to whatever the while/foreach loop expects them to be
                        if (!isset($pre_loop_context->vars_in_scope[$var_id])
                            || $type->getId() !== $pre_loop_context->vars_in_scope[$var_id]->getId()
                            || $type->from_docblock !== $pre_loop_context->vars_in_scope[$var_id]->from_docblock
                        ) {
                            $has_changes = true;
                        }
                    } elseif (isset($pre_outer_context->vars_in_scope[$var_id])) {
                        $str_type = $type->getId();

                        if ($str_type !== $pre_outer_context->vars_in_scope[$var_id]->getId()) {
                            $has_changes = true;

                            // widen the foreach context type with the initial context type
                            $inner_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                                $inner_context->vars_in_scope[$var_id],
                                $pre_outer_context->vars_in_scope[$var_id]
                            );

                            // if there's a change, invalidate related clauses
                            $pre_loop_context->removeVarFromConflictingClauses($var_id);
                        }

                        if ($str_type !== $loop_scope->loop_context->vars_in_scope[$var_id]->getId()) {
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

                $loop_scope->loop_parent_context->vars_possibly_in_scope = array_merge(
                    $inner_context->vars_possibly_in_scope,
                    $loop_scope->loop_parent_context->vars_possibly_in_scope
                );

                // if there are no changes to the types, no need to re-examine
                if (!$has_changes) {
                    break;
                }

                if ($inner_context->collect_references) {
                    foreach ($inner_context->unreferenced_vars as $var_id => $location) {
                        if (isset($loop_scope->loop_parent_context->vars_in_scope[$var_id])
                            && (!isset($loop_scope->loop_parent_context->unreferenced_vars[$var_id])
                                || $loop_scope->loop_parent_context->unreferenced_vars[$var_id] !== $location)
                        ) {
                            $statements_checker->registerVariableUse($location);
                        }
                    }
                }

                // remove vars that were defined in the foreach
                foreach ($vars_to_remove as $var_id) {
                    unset($inner_context->vars_in_scope[$var_id]);
                }

                $analyzer->setMixedCountsForFile($statements_checker->getFilePath(), $original_mixed_counts);
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

                foreach ($asserted_var_ids as $var_id) {
                    if (!isset($inner_context->vars_in_scope[$var_id])
                        || $inner_context->vars_in_scope[$var_id]->getId()
                            !== $pre_loop_context->vars_in_scope[$var_id]->getId()
                        || $inner_context->vars_in_scope[$var_id]->from_docblock
                            !== $pre_loop_context->vars_in_scope[$var_id]->from_docblock
                    ) {
                        $inner_context->vars_in_scope[$var_id] = clone $pre_loop_context->vars_in_scope[$var_id];
                    }
                }

                $inner_context->clauses = $pre_loop_context->clauses;

                $inner_context->protected_var_ids = $loop_scope->protected_var_ids;

                $statements_checker->analyze($stmts, $inner_context, $loop_scope);

                self::updateLoopScopeContexts($loop_scope, $pre_outer_context);

                $inner_context->protected_var_ids = $original_protected_var_ids;

                foreach ($post_expressions as $post_expression) {
                    if (ExpressionChecker::analyze($statements_checker, $post_expression, $inner_context) === false) {
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

        foreach ($loop_scope->loop_parent_context->vars_in_scope as $var_id => $type) {
            if ($type->isMixed() || !isset($loop_scope->loop_context->vars_in_scope[$var_id])) {
                continue;
            }

            if ($loop_scope->loop_context->vars_in_scope[$var_id]->getId() !== $type->getId()) {
                $loop_scope->loop_parent_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                    $loop_scope->loop_parent_context->vars_in_scope[$var_id],
                    $loop_scope->loop_context->vars_in_scope[$var_id]
                );

                $loop_scope->loop_parent_context->removeVarFromConflictingClauses($var_id);
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

                if ($inner_context->vars_in_scope[$var_id]->getId() !== $type->getId()) {
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

                $vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
                    $negated_pre_condition_types,
                    $inner_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_checker,
                    new CodeLocation($statements_checker->getSource(), $pre_conditions[0]),
                    $statements_checker->getSuppressedIssues()
                );

                foreach ($changed_var_ids as $var_id) {
                    if (isset($vars_in_scope_reconciled[$var_id])
                        && isset($loop_scope->loop_parent_context->vars_in_scope[$var_id])
                    ) {
                        $loop_scope->loop_parent_context->vars_in_scope[$var_id] = $vars_in_scope_reconciled[$var_id];
                    }

                    $loop_scope->loop_parent_context->removeVarFromConflictingClauses($var_id);
                }
            }
        }

        $loop_scope->loop_context->referenced_var_ids = array_merge(
            $inner_context->referenced_var_ids,
            $loop_scope->loop_context->referenced_var_ids
        );

        if ($inner_context->collect_references) {
            foreach ($inner_context->unreferenced_vars as $var_id => $location) {
                if (!isset($loop_scope->loop_context->unreferenced_vars[$var_id])) {
                    $loop_scope->loop_context->unreferenced_vars[$var_id] = $location;
                } elseif ($loop_scope->loop_context->unreferenced_vars[$var_id] !== $location) {
                    $statements_checker->registerVariableUse($location);
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
                    if ($loop_scope->loop_context->hasVariable($var)
                        && !isset($updated_loop_vars[$var])
                    ) {
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

        $asserted_var_ids = Context::getNewOrUpdatedVarIds($outer_context, $loop_context);

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

            $pre_condition_vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
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

        foreach ($asserted_var_ids as $var_id) {
            $loop_context->clauses = Context::filterClauses(
                $var_id,
                $loop_context->clauses,
                null,
                $statements_checker
            );
        }

        return $asserted_var_ids;
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
