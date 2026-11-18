<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use PhpParser\NodeFinder;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\ComplicatedExpressionException;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Internal\PhpVisitor\AssignmentMapVisitor;
use Psalm\Internal\PhpVisitor\NodeCleanerVisitor;
use Psalm\Internal\Scope\LoopScope;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\BinaryOp\VirtualBooleanAnd;
use Psalm\Storage\Assertion;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_diff_key;
use function array_filter;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_merge;
use function array_reverse;
use function array_slice;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function is_numeric;
use function is_string;
use function max;
use function min;
use function sort;
use function spl_object_id;
use function str_contains;
use function str_ends_with;
use function str_starts_with;

use const PHP_INT_MAX;

/**
 * @internal
 */
final class LoopAnalyzer
{
    /**
     * Checks an array of statements in a loop
     *
     * @param  list<PhpParser\Node\Stmt>    $stmts
     * @param  list<PhpParser\Node\Expr>    $pre_conditions
     * @param  PhpParser\Node\Expr[]        $post_expressions
     * @return false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        array $stmts,
        array $pre_conditions,
        array $post_expressions,
        LoopScope $loop_scope,
        ?Context &$continue_context = null,
        bool $is_do = false,
        bool $always_enters_loop = false,
        bool $only_last_condition_decides = false,
    ): ?bool {
        $traverser = new PhpParser\NodeTraverser;

        $loop_context = $loop_scope->loop_context;
        $loop_parent_context = $loop_scope->loop_parent_context;

        $assignment_mapper = new AssignmentMapVisitor($loop_context->self);
        $traverser->addVisitor($assignment_mapper);

        $traverser->traverse(array_merge($pre_conditions, $stmts, $post_expressions));

        $assignment_map = $assignment_mapper->getAssignmentMap();

        $loop_scope->assigned_root_var_ids = ($loop_parent_context->loop_scope?->assigned_root_var_ids ?? [])
            + $assignment_mapper->getAssignedVarIds();

        $assignment_depth = 0;

        $always_assigned_before_loop_body_vars = [];

        $pre_condition_clauses = [];

        $original_protected_var_ids = $loop_parent_context->protected_var_ids;

        $codebase = $statements_analyzer->getCodebase();

        $inner_do_context = null;

        // the variables evaluating the condition writes, with what they hold once it is evaluated
        $condition_written_var_ids = [];

        if ($pre_conditions) {
            foreach ($pre_conditions as $i => $pre_condition) {
                // only the last of the comma separated conditions of a for decides whether the loop goes on
                if ($only_last_condition_decides && $i !== count($pre_conditions) - 1) {
                    $pre_condition_clauses[$i] = [];

                    continue;
                }

                $pre_condition_id = spl_object_id($pre_condition);

                // built again once the condition is analysed (see applyPreConditionToLoopContext()), so not cached
                $pre_condition_clauses[$i] = FormulaGenerator::getFormula(
                    $pre_condition_id,
                    $pre_condition_id,
                    $pre_condition,
                    $loop_context->self,
                    $statements_analyzer,
                    $codebase,
                    cache: false,
                );
            }
        } else {
            $always_assigned_before_loop_body_vars = Context::getNewOrUpdatedVarIds(
                $loop_parent_context,
                $loop_context,
            );
        }

        $final_actions = ScopeAnalyzer::getControlActions(
            $stmts,
            $statements_analyzer->node_data,
            [],
        );

        $does_always_break = $final_actions === [ScopeAnalyzer::ACTION_BREAK];

        if ($assignment_map) {
            $first_var_id = array_keys($assignment_map)[0];

            $assignment_depth = self::getAssignmentMapDepth($first_var_id, $assignment_map);
        }

        // what an iteration defines otherwise is seen by the next one
        if ($assignment_depth === 0 && $assignment_mapper->getDefinedVarIds()) {
            $assignment_depth = 1;
        }

        if ($assignment_depth === 0 || $does_always_break) {
            $continue_context = clone $loop_context;
            $continue_context->loop_scope = $loop_scope;

            if (!$is_do) {
                foreach ($pre_conditions as $condition_offset => $pre_condition) {
                    self::applyPreConditionToLoopContext(
                        $statements_analyzer,
                        $pre_condition,
                        $pre_condition_clauses[$condition_offset],
                        $continue_context,
                        $loop_parent_context,
                        $is_do,
                        written_var_ids: $condition_written_var_ids,
                    );
                }
            }

            $continue_context->protected_var_ids = $loop_scope->protected_var_ids;

            $loop_scope->vars_at_breaks = null;
            $loop_scope->vars_at_continues = null;

            $statements_analyzer->analyze($stmts, $continue_context);
            self::updateLoopScopeContexts($loop_scope, $loop_context, $continue_context, $loop_parent_context);

            // a body that always breaks never gets to the condition
            if ($is_do && !$does_always_break) {
                $inner_do_context = clone $continue_context;

                foreach ($pre_conditions as $condition_offset => $pre_condition) {
                    $vars_after_condition = [];

                    self::applyPreConditionToLoopContext(
                        $statements_analyzer,
                        $pre_condition,
                        $pre_condition_clauses[$condition_offset],
                        $continue_context,
                        $loop_parent_context,
                        $is_do,
                        $vars_after_condition,
                        written_var_ids: $condition_written_var_ids,
                    );

                    self::applyVarsAfterCondition($inner_do_context, $vars_after_condition, $condition_offset > 0);
                }
            }

            foreach ($post_expressions as $post_expression) {
                if (ExpressionAnalyzer::analyze(
                    $statements_analyzer,
                    $post_expression,
                    $loop_context,
                ) === false
                ) {
                    return false;
                }
            }

            $loop_parent_context->vars_possibly_in_scope += $continue_context->vars_possibly_in_scope;

            self::keepNullArrayOffsetsReadOnEveryPath($loop_parent_context, $continue_context, $loop_scope);
        } else {
            $original_parent_context = clone $loop_parent_context;

            $analyzer = $statements_analyzer->getCodebase()->analyzer;

            $original_mixed_counts = $analyzer->getMixedCountsForFile($statements_analyzer->getFilePath());

            // record all the vars that existed before we did the first pass through the loop
            $pre_loop_context = clone $loop_context;

            $type_variable_tracker = $statements_analyzer->type_variable_tracker;
            $bounds_before_loop = $type_variable_tracker->snapshotBounds();
            $bounds_at_kept_pass_start = $bounds_before_loop;

            IssueBuffer::startRecording();

            // what the condition writes is recorded by the passes below, which start with the values before the loop
            // too: in a loop, this first evaluation does not know the bounds of what it computes, so it is only kept
            // when no pass evaluates the condition again
            $first_condition_written_var_ids = [];

            if (!$is_do) {
                foreach ($pre_conditions as $condition_offset => $pre_condition) {
                    self::applyPreConditionToLoopContext(
                        $statements_analyzer,
                        $pre_condition,
                        $pre_condition_clauses[$condition_offset],
                        $loop_context,
                        $loop_parent_context,
                        $is_do,
                        written_var_ids: $first_condition_written_var_ids,
                    );
                }
            }

            $continue_context = clone $loop_context;

            $continue_context->loop_scope = $loop_scope;

            $continue_context->protected_var_ids = $loop_scope->protected_var_ids;

            $loop_scope->vars_at_breaks = null;
            $loop_scope->vars_at_continues = null;

            $statements_analyzer->analyze($stmts, $continue_context);

            self::updateLoopScopeContexts($loop_scope, $loop_context, $continue_context, $original_parent_context);

            $continue_context->protected_var_ids = $original_protected_var_ids;

            if ($is_do) {
                $inner_do_context = clone $continue_context;

                foreach ($pre_conditions as $condition_offset => $pre_condition) {
                    $vars_after_condition = [];

                    $always_assigned_before_loop_body_vars = [...self::applyPreConditionToLoopContext(
                        $statements_analyzer,
                        $pre_condition,
                        $pre_condition_clauses[$condition_offset],
                        $continue_context,
                        $loop_parent_context,
                        $is_do,
                        $vars_after_condition,
                        $first_condition_written_var_ids,
                    ), ...$always_assigned_before_loop_body_vars];

                    self::applyVarsAfterCondition($inner_do_context, $vars_after_condition, $condition_offset > 0);
                }
            }

            $always_assigned_before_loop_body_vars = array_unique($always_assigned_before_loop_body_vars);

            foreach ($post_expressions as $post_expression) {
                if (ExpressionAnalyzer::analyze($statements_analyzer, $post_expression, $continue_context) === false) {
                    return false;
                }
            }

            $recorded_issues = IssueBuffer::clearRecordingLevel();
            IssueBuffer::stopRecording();

            // the types the variables the loop defines start an iteration with
            $loop_var_types_at_start = [];
            $unset_in_loop_vars = [];
            $possibly_defined_in_loop_vars = [];

            // the data flow of the last pass is linked to the reads of the first one by one more pass
            $max_passes = $assignment_depth;
            $data_flow_pass_added = false;
            $widening_passes = 0;
            $propagation_passes = 0;
            $feeding_var_ids = $assignment_mapper->getFeedingVarIds();
            $max_propagation_passes = count($feeding_var_ids);
            $previously_changed_feeding_var_ids = [];
            $widening_bounds = null;
            $force_pass = false;

            for ($i = 0; $i < $max_passes; ++$i) {
                $vars_to_remove = [];

                $loop_scope->iteration_count++;

                $has_changes = false;

                // reset the $continue_context to what it was before we started the analysis,
                // but union the types with what's in the loop scope

                foreach ($continue_context->vars_in_scope as $var_id => $type) {
                    if (in_array($var_id, $always_assigned_before_loop_body_vars, true)) {
                        // set the vars to whatever the while/foreach loop expects them to be
                        if (!isset($pre_loop_context->vars_in_scope[$var_id])
                            || !$type->equals($pre_loop_context->vars_in_scope[$var_id])
                        ) {
                            $has_changes = true;
                        }
                    } elseif (isset($original_parent_context->vars_in_scope[$var_id])) {
                        if (!$type->equals($original_parent_context->vars_in_scope[$var_id])) {
                            $has_changes = true;

                            // widen the foreach context type with the initial context type
                            $continue_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                                $continue_context->vars_in_scope[$var_id],
                                $original_parent_context->vars_in_scope[$var_id],
                            );

                            // if there's a change, invalidate related clauses
                            $pre_loop_context->removeVarFromConflictingClauses($var_id);

                            $loop_parent_context->possibly_assigned_var_ids[$var_id] = true;
                        }

                        if (isset($loop_context->vars_in_scope[$var_id])
                            && !$type->equals($loop_context->vars_in_scope[$var_id])
                        ) {
                            $has_changes = true;

                            // widen the foreach context type with the initial context type
                            $continue_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                                $continue_context->vars_in_scope[$var_id],
                                $loop_context->vars_in_scope[$var_id],
                            );

                            // if there's a change, invalidate related clauses
                            $pre_loop_context->removeVarFromConflictingClauses($var_id);
                        }
                    } else {
                        // the next iteration starts with what this one left in the variable (see below)
                        if (self::isLoopVar($var_id)
                            && (!isset($loop_var_types_at_start[$var_id])
                                || !$type->setPossiblyUndefined(false)->equals($loop_var_types_at_start[$var_id]))
                        ) {
                            $has_changes = true;
                        }

                        $vars_to_remove[] = $var_id;
                    }
                }

                // a variable the body unset is gone where the next iteration starts only if the body ran:
                // it's possibly undefined there
                foreach ($original_parent_context->vars_in_scope as $var_id => $_) {
                    if (!isset($continue_context->vars_in_scope[$var_id])
                        && !isset($unset_in_loop_vars[$var_id])
                        && self::isLoopVar($var_id)
                    ) {
                        $unset_in_loop_vars[$var_id] = true;
                        $has_changes = true;
                    }
                }

                // a variable an iteration only possibly defines (a foreach over a possibly empty array, the right
                // operand of `||`) is possibly defined where the next one starts
                foreach ($continue_context->vars_possibly_in_scope as $var_id => $_) {
                    if (!isset($continue_context->vars_in_scope[$var_id])
                        && !isset($pre_loop_context->vars_possibly_in_scope[$var_id])
                        && !isset($possibly_defined_in_loop_vars[$var_id])
                        && self::isLoopVar($var_id)
                    ) {
                        $possibly_defined_in_loop_vars[$var_id] = true;
                        $has_changes = true;
                    }
                }

                foreach ($unset_in_loop_vars as $var_id => $_) {
                    if (!isset($continue_context->vars_in_scope[$var_id])) {
                        $continue_context->vars_in_scope[$var_id]
                            = $original_parent_context->vars_in_scope[$var_id]->setPossiblyUndefined(true);
                    }
                }

                // the variables the loop defines are possibly defined where the next iteration starts, holding what
                // an iteration left in them where it ended or continued: the other ones defined in the loop go
                foreach ($loop_scope->possibly_defined_loop_vars as $var_id => $type) {
                    if (!isset($continue_context->vars_in_scope[$var_id])
                        && !isset($original_parent_context->vars_in_scope[$var_id])
                        && !in_array($var_id, $always_assigned_before_loop_body_vars, true)
                        && !$is_do
                    ) {
                        $vars_to_remove[] = $var_id;

                        // only a `continue` defines it
                        if (self::isLoopVar($var_id)
                            && (!isset($loop_var_types_at_start[$var_id])
                                || !$type->setPossiblyUndefined(false)->equals($loop_var_types_at_start[$var_id]))
                        ) {
                            $has_changes = true;
                        }
                    }
                }

                $continue_context->has_returned = false;

                $loop_parent_context->vars_possibly_in_scope += $continue_context->vars_possibly_in_scope;

                self::keepNullArrayOffsetsReadOnEveryPath($loop_parent_context, $continue_context, $loop_scope);

                // if there are no changes to the types, no need to re-examine
                if (!$has_changes && !$force_pass) {
                    break;
                }

                $force_pass = false;

                foreach ($vars_to_remove as $var_id) {
                    if (!self::isLoopVar($var_id) || isset($continue_context->references_in_scope[$var_id])) {
                        $continue_context->removePossibleReference($var_id);

                        continue;
                    }

                    $loop_var_types_at_start[$var_id] = Type::combineUnionTypes(
                        $continue_context->vars_in_scope[$var_id] ?? null,
                        $loop_scope->possibly_defined_loop_vars[$var_id] ?? null,
                    )->setPossiblyUndefined(false);
                    $continue_context->vars_in_scope[$var_id] = $loop_var_types_at_start[$var_id]
                        ->setPossiblyUndefined(true);
                    $continue_context->vars_possibly_in_scope[$var_id] = true;
                }

                $continue_context->clauses = $pre_loop_context->clauses;
                $continue_context->byref_constraints = $pre_loop_context->byref_constraints;

                $vars_at_iteration_start = $continue_context->vars_in_scope;

                $analyzer->setMixedCountsForFile($statements_analyzer->getFilePath(), $original_mixed_counts);
                $bounds_at_kept_pass_start = $type_variable_tracker->snapshotBounds();
                IssueBuffer::startRecording();

                // what a pass computed of the condition (the assertions of its operands too) and of the body is no
                // longer true of the next one
                $traverser = new PhpParser\NodeTraverser;

                $traverser->addVisitor(
                    new NodeCleanerVisitor(
                        $statements_analyzer->node_data,
                    ),
                );
                $traverser->traverse([...$pre_conditions, ...$stmts]);

                if (!$is_do) {
                    foreach ($pre_conditions as $condition_offset => $pre_condition) {
                        self::applyPreConditionToLoopContext(
                            $statements_analyzer,
                            $pre_condition,
                            $pre_condition_clauses[$condition_offset],
                            $continue_context,
                            $loop_parent_context,
                            false,
                            written_var_ids: $condition_written_var_ids,
                        );
                    }
                }

                foreach ($always_assigned_before_loop_body_vars as $var_id) {
                    if ((!isset($continue_context->vars_in_scope[$var_id])
                            || self::differ(
                                $continue_context->vars_in_scope[$var_id],
                                $pre_loop_context->vars_in_scope[$var_id],
                            )
                            || $continue_context->vars_in_scope[$var_id]->from_docblock
                                !== $pre_loop_context->vars_in_scope[$var_id]->from_docblock
                            // the value of an iteration is not the one the last one ended with
                            || array_keys($continue_context->vars_in_scope[$var_id]->parent_nodes)
                                !== array_keys($pre_loop_context->vars_in_scope[$var_id]->parent_nodes)
                        )
                    ) {
                        if (isset($pre_loop_context->vars_in_scope[$var_id])) {
                            $continue_context->vars_in_scope[$var_id] = $pre_loop_context->vars_in_scope[$var_id];
                        } else {
                            $continue_context->removePossibleReference($var_id);
                        }
                    }
                }

                $continue_context->clauses = $pre_loop_context->clauses;

                $continue_context->protected_var_ids = $loop_scope->protected_var_ids;

                $vars_at_pass_start = $continue_context->vars_in_scope;

                $loop_scope->vars_at_breaks = null;
                $loop_scope->vars_at_continues = null;

                $statements_analyzer->analyze($stmts, $continue_context);

                self::updateLoopScopeContexts($loop_scope, $loop_context, $continue_context, $original_parent_context);

                $continue_context->protected_var_ids = $original_protected_var_ids;

                if ($is_do) {
                    $inner_do_context = clone $continue_context;

                    foreach ($pre_conditions as $condition_offset => $pre_condition) {
                        $vars_after_condition = [];

                        self::applyPreConditionToLoopContext(
                            $statements_analyzer,
                            $pre_condition,
                            $pre_condition_clauses[$condition_offset],
                            $continue_context,
                            $loop_parent_context,
                            $is_do,
                            $vars_after_condition,
                            written_var_ids: $condition_written_var_ids,
                        );

                        self::applyVarsAfterCondition(
                            $inner_do_context,
                            $vars_after_condition,
                            $condition_offset > 0,
                        );
                    }
                }

                foreach ($post_expressions as $post_expression) {
                    if (ExpressionAnalyzer::analyze(
                        $statements_analyzer,
                        $post_expression,
                        $continue_context,
                    ) === false) {
                        return false;
                    }
                }

                $recorded_issues = IssueBuffer::clearRecordingLevel();

                IssueBuffer::stopRecording();

                // a range the passes keep shifting (`$i += rand(1, 3)`) would go further on the iterations not
                // analysed: its bound is moved to the next int the loop compares it with (`if ($i < 10) $i++`) and
                // the body analysed once more with it. The pass after that one starts with what the widened pass
                // ended with, which gets back the bounds the body enforces (`$i = min(10, $i + 1)`). A range that
                // still goes past that is widened again, and then unbounded.
                if ($i + 1 === $max_passes && $widening_passes < 4) {
                    $widened_var_types = [];

                    if ($widening_passes !== 1) {
                        $vars_at_start = array_diff_key(
                            $vars_at_iteration_start,
                            array_flip($always_assigned_before_loop_body_vars),
                        );
                        $vars_at_end = self::getVarsStartingNextIteration(
                            $continue_context,
                            $loop_scope,
                            $post_expressions,
                        );

                        $widened_var_types = self::getWidenedIntRanges($vars_at_start, $vars_at_end, null);

                        if ($widened_var_types && $widening_passes !== 3) {
                            $widening_bounds ??= self::getWideningBounds(
                                [...$pre_conditions, ...$stmts, ...$post_expressions],
                            );

                            $widened_var_types = self::getWidenedIntRanges(
                                $vars_at_start,
                                $vars_at_end,
                                $widening_bounds,
                            );
                        }
                    }

                    if ($widened_var_types || $widening_passes === 1) {
                        $continue_context->vars_in_scope = $widened_var_types + $continue_context->vars_in_scope;
                        ++$max_passes;
                        ++$widening_passes;
                        $force_pass = true;
                    } else {
                        $widening_passes = 4;
                    }
                }

                // the passes counted from the assignment map don't cover every chain of copies (`$a = $b; $b = $c;
                // $c = "s";`): a type still changing in the last pass is carried to the variables it goes into by
                // one more pass. Along a chain, the variables a pass changes aren't those the previous one changed:
                // the same ones changing again keep changing on their own (`$e[$k] = [$e[$k] ?? null]`) and would
                // never settle.
                if ($i + 1 === $max_passes && $propagation_passes < $max_propagation_passes) {
                    $changed_feeding_var_ids = self::getChangedFeedingVarIds(
                        $feeding_var_ids,
                        $vars_at_iteration_start,
                        self::getVarsStartingNextIteration($continue_context, $loop_scope, $post_expressions),
                    );

                    if ($changed_feeding_var_ids
                        && $changed_feeding_var_ids != $previously_changed_feeding_var_ids
                    ) {
                        ++$max_passes;
                        ++$propagation_passes;
                    }

                    $previously_changed_feeding_var_ids = $changed_feeding_var_ids;
                }

                if ($i + 1 === $max_passes
                    && !$data_flow_pass_added
                    && $codebase->find_unused_variables
                    && self::hasNewParentNodes($vars_at_pass_start, $continue_context->vars_in_scope)
                ) {
                    ++$max_passes;
                    $data_flow_pass_added = true;
                    $force_pass = true;
                }
            }

            if (!$condition_written_var_ids) {
                $condition_written_var_ids = $first_condition_written_var_ids;
            }

            // like their issues, the bounds the passes before the last one gave the type variables minted before the
            // loop are dropped: the last pass records them again with the types the loop leaves. They stay until then,
            // as the last pass reads them before recording its own (a value taken from a collection a previous
            // iteration filled).
            $type_variable_tracker->discardBoundsRecordedBetween(
                $bounds_before_loop,
                $bounds_at_kept_pass_start,
            );

            if ($recorded_issues) {
                foreach ($recorded_issues as $recorded_issue) {
                    // if we're not in any loops then this will just result in the issue being emitted
                    IssueBuffer::bubbleUp($recorded_issue);
                }
            }
        }

        $does_sometimes_break = in_array(ScopeAnalyzer::ACTION_BREAK, $loop_scope->final_actions, true);
        $does_always_break = $loop_scope->final_actions === [ScopeAnalyzer::ACTION_BREAK];

        $loop_nodes = [...$pre_conditions, ...$stmts, ...$post_expressions];

        // the body is analysed in the continue context: the loop context does not get what it rebinds
        if (!$does_always_break) {
            $loop_parent_context->detachReferencesReboundIn($continue_context, $statements_analyzer, $loop_nodes);
        }

        $loop_parent_context->mergeConstraintsOfReferencesReboundIn(
            $continue_context,
            $loop_nodes,
            $statements_analyzer->getCodebase(),
        );

        if ($does_sometimes_break) {
            foreach ($loop_scope->possibly_redefined_loop_parent_vars as $var => $type) {
                $loop_parent_context->vars_in_scope[$var] = Type::combineUnionTypes(
                    $type,
                    $loop_parent_context->vars_in_scope[$var],
                );

                $loop_parent_context->possibly_assigned_var_ids[$var] = true;
            }
        }

        foreach ($loop_parent_context->vars_in_scope as $var_id => $type) {
            if (!isset($loop_context->vars_in_scope[$var_id])) {
                continue;
            }

            if (self::differ($loop_context->vars_in_scope[$var_id], $type)) {
                $loop_parent_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                    $loop_parent_context->vars_in_scope[$var_id],
                    $loop_context->vars_in_scope[$var_id],
                );

                $loop_parent_context->removeVarFromConflictingClauses($var_id);
            } else {
                $loop_parent_context->vars_in_scope[$var_id]
                    = $loop_parent_context->vars_in_scope[$var_id]->addParentNodes(
                        $loop_context->vars_in_scope[$var_id]->parent_nodes,
                    )
                ;
            }
        }

        if (!$does_always_break) {
            foreach ($loop_parent_context->vars_in_scope as $var_id => $type) {
                if (!isset($continue_context->vars_in_scope[$var_id])) {
                    $loop_parent_context->removePossibleReference($var_id);
                    continue;
                }

                if ($continue_context->vars_in_scope[$var_id]->hasMixed()) {
                    $continue_context->vars_in_scope[$var_id]
                        = $continue_context->vars_in_scope[$var_id]->addParentNodes(
                            $loop_parent_context->vars_in_scope[$var_id]->parent_nodes,
                        )
                    ;

                    // a mixed value the loop condition narrowed (`non-empty-mixed`) is not narrowed where the loop
                    // is left, so it is combined with the type it had before the loop
                    $loop_parent_context->vars_in_scope[$var_id] = $loop_parent_context->vars_in_scope[$var_id]
                        ->hasMixed()
                        ? Type::combineUnionTypes(
                            $loop_parent_context->vars_in_scope[$var_id],
                            $continue_context->vars_in_scope[$var_id],
                        )
                        : $continue_context->vars_in_scope[$var_id];
                    $loop_parent_context->removeVarFromConflictingClauses($var_id);

                    continue;
                }

                if (self::differ($continue_context->vars_in_scope[$var_id], $type)) {
                    $loop_parent_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $loop_parent_context->vars_in_scope[$var_id],
                        $continue_context->vars_in_scope[$var_id],
                    );

                    $loop_parent_context->removeVarFromConflictingClauses($var_id);
                } else {
                    $loop_parent_context->vars_in_scope[$var_id] =
                        $loop_parent_context->vars_in_scope[$var_id]->setParentNodes([
                            ...$loop_parent_context->vars_in_scope[$var_id]->parent_nodes,
                            ...$continue_context->vars_in_scope[$var_id]->parent_nodes,
                        ])
                    ;
                }
            }
        }

        $loop_scope->condition_written_vars = $condition_written_var_ids;

        // all the comma separated conditions of a for are evaluated, but only the first operand of the && of a while
        foreach ($only_last_condition_decides ? $pre_conditions : array_slice($pre_conditions, 0, 1) as $evaluated) {
            self::addAlwaysWrittenVarIds(
                $evaluated,
                $loop_context->self,
                $statements_analyzer,
                $loop_scope->condition_always_written_var_ids,
            );
        }

        if ($pre_conditions && $pre_condition_clauses && !$does_sometimes_break) {
            // if the loop contains an assertion and there are no break statements, we can negate that assertion
            // and apply it to the current context

            $deciding_condition = null;

            foreach ($pre_conditions as $i => $pre_condition) {
                if (!$only_last_condition_decides || $i === count($pre_conditions) - 1) {
                    $deciding_condition = $deciding_condition
                        ? new VirtualBooleanAnd($deciding_condition, $pre_condition, $pre_condition->getAttributes())
                        : $pre_condition;
                }
            }

            try {
                $negated_pre_condition_clauses = $deciding_condition
                    ? FormulaGenerator::getNegatedFormula(
                        array_merge(...$pre_condition_clauses),
                        $deciding_condition,
                        spl_object_id($deciding_condition),
                        $loop_context->self,
                        $statements_analyzer,
                        $codebase,
                    )
                    : [];
            } catch (ComplicatedExpressionException) {
                $negated_pre_condition_clauses = [];
            }

            $negated_pre_condition_types = Algebra::getTruthsFromFormula($negated_pre_condition_clauses);
            $loop_scope->exit_truths = $negated_pre_condition_types;

            // a do-while is left after its body, where its condition is not yet assumed; a while or for loop is also
            // left before its body ever runs: the parent context holds the types of both ways out, but what the
            // condition writes holds what evaluating it left on either
            self::applyExitTruths(
                $statements_analyzer,
                $negated_pre_condition_types,
                $is_do ? ($inner_do_context ?? $continue_context) : $loop_parent_context,
                $is_do ? [] : $condition_written_var_ids,
                $loop_scope->condition_always_written_var_ids,
                $loop_parent_context,
                new CodeLocation($statements_analyzer->getSource(), $pre_conditions[0]),
            );
        }

        if ($always_enters_loop) {
            // Variables assigned before the loop body (e.g. a foreach key/value)
            // that are never reassigned inside the body are guaranteed to hold
            // their loop-assigned type after the loop, even if it breaks or
            // continues, so they must not be merged back to their pre-loop type.
            $always_assigned_unmodified_vars = array_values(array_filter(
                $always_assigned_before_loop_body_vars,
                static fn(string $var_id): bool => !isset($assignment_map[$var_id]),
            ));

            self::setLoopVars(
                $continue_context,
                $loop_parent_context,
                $loop_scope,
                $always_assigned_unmodified_vars,
            );
        }

        $loop_parent_context->removeClausesOfVarsChangedIn($loop_context);
        $loop_parent_context->removeClausesOfVarsChangedIn($continue_context);
        // a break or continue out of a nested scope that does not end in the loop body, such as a switch case left
        // through its breaks, changed these
        $loop_parent_context->removeClausesOfVarsChangedWith($loop_scope->parent_remove_vars_at_jumps);

        if ($inner_do_context) {
            // what the condition throws is thrown by the loop too
            $inner_do_context->mergeExceptions($continue_context);
            $continue_context = $inner_do_context;
        }

        // Track references set in the loop to make sure they aren't reused later
        $loop_parent_context->updateReferencesPossiblyFromConfusingScope(
            $continue_context,
            $statements_analyzer,
        );

        return null;
    }

    /**
     * After the loop, an offset was read only if it was read where the loop isn't entered, at the end of the body and
     * at every `break` and `continue`
     *
     * @psalm-capabilities read-props|write-props
     */
    private static function keepNullArrayOffsetsReadOnEveryPath(
        Context $loop_parent_context,
        Context $continue_context,
        LoopScope $loop_scope,
    ): void {
        $loop_parent_context->intersectNullArrayOffsets($continue_context);

        if ($loop_scope->null_array_offsets_read_at_jumps !== null) {
            $loop_parent_context->null_array_offsets_read = array_intersect_key(
                $loop_parent_context->null_array_offsets_read,
                $loop_scope->null_array_offsets_read_at_jumps,
            );
        }
    }

    /**
     * @param array<string, Union> $before
     * @param array<string, Union> $after
     * @psalm-pure
     */
    private static function hasNewParentNodes(array $before, array $after): bool
    {
        foreach ($after as $var_id => $type) {
            if ($type->parent_nodes
                && isset($before[$var_id])
                && array_diff_key($type->parent_nodes, $before[$var_id]->parent_nodes)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, Union> $init_var_types
     * @param array<string, int> $assigned_var_ids
     * @param list<PhpParser\Node\Expr> $pre_conditions
     * @param PhpParser\Node\Expr[] $post_expressions
     * @return false|null
     */
    public static function analyzeForOrWhile(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\For_|PhpParser\Node\Stmt\While_ $stmt,
        Context $context,
        bool $while_true,
        array $init_var_types,
        array $assigned_var_ids,
        array $pre_conditions,
        array $post_expressions,
    ): ?bool {
        $pre_context = null;

        if ($while_true) {
            $pre_context = clone $context;
        }

        $for_context = clone $context;

        $for_context->inside_loop = true;
        $for_context->break_types[] = 'loop';

        $codebase = $statements_analyzer->getCodebase();

        if ($codebase->alter_code && $for_context->branch_point === null) {
            $for_context->branch_point = (int) $stmt->getAttribute('startFilePos');
        }

        $loop_scope = new LoopScope($for_context, $context);

        $loop_scope->protected_var_ids = array_merge(
            $assigned_var_ids,
            $context->protected_var_ids,
        );

        if (self::analyze(
            $statements_analyzer,
            $stmt->stmts,
            $pre_conditions,
            $post_expressions,
            $loop_scope,
            $inner_loop_context,
            false,
            false,
            $stmt instanceof PhpParser\Node\Stmt\For_,
        ) === false) {
            return false;
        }

        if (!$inner_loop_context) {
            throw new UnexpectedValueException('There should be an inner loop context');
        }

        $always_enters_loop = $while_true || self::doesEnterLoop($statements_analyzer, $stmt, $init_var_types);

        $can_leave_loop = !$while_true
            || in_array(ScopeAnalyzer::ACTION_BREAK, $loop_scope->final_actions, true);

        if ($always_enters_loop && $can_leave_loop) {
            self::setLoopVars($inner_loop_context, $context, $loop_scope, is_only_left_by_break: $while_true);

            if (!$while_true && !in_array(ScopeAnalyzer::ACTION_BREAK, $loop_scope->final_actions, true)) {
                // the loop is left after an iteration, once the condition evaluated again is false
                self::applyExitTruths(
                    $statements_analyzer,
                    $loop_scope->exit_truths,
                    $context,
                    $loop_scope->condition_written_vars,
                    $loop_scope->condition_always_written_var_ids,
                    $context,
                    null,
                );
            }
        }

        if ($can_leave_loop) {
            self::addVarsWrittenByTheExitingCondition($context, $loop_scope);

            // the condition is evaluated before the loop is left, whatever happens in the body: all the
            // comma separated expressions of a for, but only the first operand of the && of a while
            $evaluated_conditions = $stmt instanceof PhpParser\Node\Stmt\For_
                ? $pre_conditions
                : array_slice($pre_conditions, 0, 1);

            $defined_here = [];

            $is_only_left_by_the_condition = !in_array(ScopeAnalyzer::ACTION_BREAK, $loop_scope->final_actions, true);

            foreach ($evaluated_conditions as $evaluated_condition) {
                self::setVarsAssignedInCondition(
                    $statements_analyzer,
                    $evaluated_condition,
                    $context,
                    $inner_loop_context,
                    $loop_scope->exit_truths,
                    $defined_here,
                    $is_only_left_by_the_condition,
                );
            }
        }

        $for_context->loop_scope = null;

        if ($can_leave_loop) {
            $context->vars_possibly_in_scope = [
                ...$context->vars_possibly_in_scope,
                ...$for_context->vars_possibly_in_scope,
            ];
        } elseif ($pre_context) {
            $context->vars_possibly_in_scope = $pre_context->vars_possibly_in_scope;
        }

        if ($context->collect_exceptions) {
            $context->mergeExceptions($for_context);
            $context->mergeExceptions($inner_loop_context);
        }

        return null;
    }

    /**
     * A loop that a break leaves too is left where its condition is false with what evaluating the condition wrote
     * (`$t--`), which neither what the body ends with nor what the breaks leave holds
     */
    public static function addVarsWrittenByTheExitingCondition(Context $context, LoopScope $loop_scope): void
    {
        if (!in_array(ScopeAnalyzer::ACTION_BREAK, $loop_scope->final_actions, true)) {
            return;
        }

        foreach ($loop_scope->condition_written_vars as $var_id => $type) {
            if (isset($context->vars_in_scope[$var_id])) {
                $context->vars_in_scope[$var_id] = Type::combineUnionTypes($context->vars_in_scope[$var_id], $type);
            }
        }
    }

    /**
     * Narrows the variables of the context to what the condition being false says of them where the loop is left
     *
     * @param array<string, list<list<Assertion>>> $exit_truths
     * @param array<string, Union> $condition_written_vars what the variables the condition writes hold where the loop
     *                                                    is left, rather than what the exit context says
     */
    private static function applyExitTruths(
        StatementsAnalyzer $statements_analyzer,
        array $exit_truths,
        Context $exit_context,
        array $condition_written_vars,
        array $always_written_var_ids,
        Context $context,
        ?CodeLocation $code_location,
    ): void {
        $exit_vars = $exit_context->vars_in_scope;
        $overridden_var_ids = [];

        foreach ($condition_written_vars as $var_id => $type) {
            if (isset($exit_vars[$var_id])) {
                $exit_vars[$var_id] = $type;
                $overridden_var_ids[$var_id] = true;
            }
        }

        $changed_var_ids = [];

        [$vars_in_scope_reconciled, $_] = Reconciler::reconcileKeyedTypes(
            $exit_truths,
            [],
            $exit_vars,
            $exit_context->references_in_scope,
            $changed_var_ids,
            [],
            $statements_analyzer,
            [],
            true,
            $code_location,
        );

        // a write the condition can skip (`$a && $n-- > 0`) leaves the value the variable had before, which the
        // condition being false narrows too
        $skipped_write_var_ids = array_diff_key($overridden_var_ids, $always_written_var_ids);
        $vars_in_scope_with_skipped_writes = [];

        if ($skipped_write_var_ids) {
            $skipped_write_changed_var_ids = [];

            [$vars_in_scope_with_skipped_writes, $_] = Reconciler::reconcileKeyedTypes(
                $exit_truths,
                [],
                $exit_context->vars_in_scope,
                $exit_context->references_in_scope,
                $skipped_write_changed_var_ids,
                [],
                $statements_analyzer,
                [],
                true,
                null,
            );
        }

        // what the condition wrote is the value the loop is left with, whether or not its being false narrows it
        foreach ($changed_var_ids + $overridden_var_ids as $var_id => $_) {
            if (isset($vars_in_scope_reconciled[$var_id])
                && isset($context->vars_in_scope[$var_id])
            ) {
                $context->vars_in_scope[$var_id] = isset($skipped_write_var_ids[$var_id])
                    ? Type::combineUnionTypes(
                        $vars_in_scope_with_skipped_writes[$var_id] ?? $exit_context->vars_in_scope[$var_id],
                        $vars_in_scope_reconciled[$var_id],
                    )
                    : $vars_in_scope_reconciled[$var_id];
            }

            $context->removeVarFromConflictingClauses($var_id);
        }
    }

    /**
     * The variables the expression writes whenever it is evaluated: not in the right operand of a short-circuiting
     * operator
     *
     * @param array<string, true> $var_ids
     */
    private static function addAlwaysWrittenVarIds(
        PhpParser\Node\Expr $expr,
        ?string $this_class_name,
        StatementsAnalyzer $statements_analyzer,
        array &$var_ids,
    ): void {
        if ($expr instanceof PhpParser\Node\Expr\Assign
            || ($expr instanceof PhpParser\Node\Expr\AssignOp
                && !$expr instanceof PhpParser\Node\Expr\AssignOp\Coalesce)
        ) {
            self::addAlwaysWrittenVarIds($expr->expr, $this_class_name, $statements_analyzer, $var_ids);
        } elseif ($expr instanceof PhpParser\Node\Expr\BooleanNot
            || $expr instanceof PhpParser\Node\Expr\Cast
            || $expr instanceof PhpParser\Node\Expr\UnaryMinus
            || $expr instanceof PhpParser\Node\Expr\UnaryPlus
        ) {
            self::addAlwaysWrittenVarIds($expr->expr, $this_class_name, $statements_analyzer, $var_ids);

            return;
        } elseif ($expr instanceof PhpParser\Node\Expr\BinaryOp) {
            self::addAlwaysWrittenVarIds($expr->left, $this_class_name, $statements_analyzer, $var_ids);

            if (!$expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                && !$expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                && !$expr instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
                && !$expr instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
                && !$expr instanceof PhpParser\Node\Expr\BinaryOp\Coalesce
            ) {
                self::addAlwaysWrittenVarIds($expr->right, $this_class_name, $statements_analyzer, $var_ids);
            }

            return;
        } elseif (($expr instanceof PhpParser\Node\Expr\FuncCall
                || $expr instanceof PhpParser\Node\Expr\MethodCall
                || $expr instanceof PhpParser\Node\Expr\StaticCall
                || $expr instanceof PhpParser\Node\Expr\New_)
            && !$expr->isFirstClassCallable()
        ) {
            // the arguments of a call are evaluated, and those passed by reference written
            foreach ($expr->getArgs() as $arg) {
                self::addAlwaysWrittenVarIds($arg->value, $this_class_name, $statements_analyzer, $var_ids);

                if ($statements_analyzer->node_data->isWrittenByReference($arg->value)) {
                    $var_id = ExpressionIdentifier::getExtendedVarId(
                        $arg->value,
                        $this_class_name,
                        $statements_analyzer,
                    );

                    if ($var_id !== null) {
                        $var_ids[$var_id] = true;
                    }
                }
            }

            return;
        } elseif (!$expr instanceof PhpParser\Node\Expr\PreInc
            && !$expr instanceof PhpParser\Node\Expr\PreDec
            && !$expr instanceof PhpParser\Node\Expr\PostInc
            && !$expr instanceof PhpParser\Node\Expr\PostDec
        ) {
            return;
        }

        $var_id = ExpressionIdentifier::getExtendedVarId($expr->var, $this_class_name, $statements_analyzer);

        if ($var_id !== null) {
            $var_ids[$var_id] = true;
        }
    }

    /**
     * Defines the variables the expression assigns whenever it is evaluated, with the type of what they are assigned
     * (and what the condition being false says of it, where the loop is only left by the condition)
     *
     * @param array<string, list<list<Assertion>>> $exit_truths
     * @param array<string, true> $defined_here the variables an earlier assignment of the conditions defined: a later
     *                                           one overrides them
     * @param bool $is_only_left_by_the_condition whether no break leaves the loop: a variable that existed before
     *                                            the loop then holds what the condition assigned as well
     */
    private static function setVarsAssignedInCondition(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $expr,
        Context $context,
        Context $inner_loop_context,
        array $exit_truths,
        array &$defined_here,
        bool $is_only_left_by_the_condition,
    ): void {
        if ($expr instanceof PhpParser\Node\Expr\Assign) {
            // the value is evaluated before it is assigned
            self::setVarsAssignedInCondition(
                $statements_analyzer,
                $expr->expr,
                $context,
                $inner_loop_context,
                $exit_truths,
                $defined_here,
                $is_only_left_by_the_condition,
            );

            if ($expr->var instanceof PhpParser\Node\Expr\Variable && is_string($expr->var->name)) {
                $var_id = '$' . $expr->var->name;
                $assigned_type = $statements_analyzer->node_data->getType($expr->expr);

                if ($assigned_type
                    && !$is_only_left_by_the_condition
                    && isset($context->vars_in_scope[$var_id])
                    && !isset($defined_here[$var_id])
                    && isset($inner_loop_context->vars_in_scope[$var_id])
                ) {
                    // a break leaves the loop too: the variable holds what the break or the condition left
                    $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $context->vars_in_scope[$var_id],
                        $assigned_type->setParentNodes($inner_loop_context->vars_in_scope[$var_id]->parent_nodes),
                    );
                } elseif ($assigned_type
                    && isset($inner_loop_context->vars_in_scope[$var_id])
                ) {
                    // the loop was left because the condition is false: that is true of what was assigned
                    if (isset($exit_truths[$var_id])) {
                        $changed_var_ids = [];

                        [$reconciled_vars, $_] = Reconciler::reconcileKeyedTypes(
                            [$var_id => $exit_truths[$var_id]],
                            [],
                            [$var_id => $assigned_type],
                            [],
                            $changed_var_ids,
                            [],
                            $statements_analyzer,
                            [],
                            true,
                            new CodeLocation($statements_analyzer->getSource(), $expr),
                        );

                        $assigned_type = $reconciled_vars[$var_id] ?? $assigned_type;
                    }

                    // the type of the value, with the data flow of the variable
                    $context->vars_in_scope[$var_id] = $assigned_type->setParentNodes(
                        $inner_loop_context->vars_in_scope[$var_id]->parent_nodes,
                    );
                    $defined_here[$var_id] = true;
                }
            }
        } elseif ($expr instanceof PhpParser\Node\Expr\BooleanNot || $expr instanceof PhpParser\Node\Expr\Cast) {
            self::setVarsAssignedInCondition(
                $statements_analyzer,
                $expr->expr,
                $context,
                $inner_loop_context,
                $exit_truths,
                $defined_here,
                $is_only_left_by_the_condition,
            );
        } elseif ($expr instanceof PhpParser\Node\Expr\BinaryOp) {
            self::setVarsAssignedInCondition(
                $statements_analyzer,
                $expr->left,
                $context,
                $inner_loop_context,
                $exit_truths,
                $defined_here,
                $is_only_left_by_the_condition,
            );

            // the right operand of a short-circuiting operator may not be evaluated
            if (!$expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                && !$expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                && !$expr instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
                && !$expr instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
                && !$expr instanceof PhpParser\Node\Expr\BinaryOp\Coalesce
            ) {
                self::setVarsAssignedInCondition(
                    $statements_analyzer,
                    $expr->right,
                    $context,
                    $inner_loop_context,
                    $exit_truths,
                    $defined_here,
                    $is_only_left_by_the_condition,
                );
            }
        }
    }

    /**
     * @param list<string> $always_assigned_before_loop_body_vars
     * @param bool $is_only_left_by_break whether the loop is only left by a break (`while (true)`): the variables then
     *                                    hold what they held at the breaks, not what an iteration ended with
     */
    public static function setLoopVars(
        Context $inner_context,
        Context $context,
        LoopScope $loop_scope,
        array $always_assigned_before_loop_body_vars = [],
        bool $is_only_left_by_break = false,
    ): void {
        if ($is_only_left_by_break
            && $loop_scope->vars_at_breaks !== null
            && !$loop_scope->breaks_through_finally
        ) {
            foreach ($context->vars_in_scope as $var_id => $_) {
                if (!isset($loop_scope->vars_at_breaks[$var_id]) && self::isLoopVar($var_id)) {
                    // unset at every break
                    $context->remove($var_id);
                }
            }

            foreach ($loop_scope->vars_at_breaks as $var_id => $type) {
                $defined_in_loop_type = $loop_scope->possibly_defined_loop_parent_vars[$var_id] ?? null;

                if ($type->isNever()) {
                    // the passes over the body found no value at the breaks: what an iteration ends with is kept
                    if ($defined_in_loop_type && isset($inner_context->vars_in_scope[$var_id])) {
                        $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $inner_context->vars_in_scope[$var_id],
                            $defined_in_loop_type,
                        );
                    }

                    continue;
                }

                if ($defined_in_loop_type) {
                    // possibly undefined after the loop if a break of any pass leaves it undefined
                    if ($defined_in_loop_type->possibly_undefined) {
                        $type = $type->setPossiblyUndefined(true);
                    }
                } elseif (!isset($context->vars_in_scope[$var_id])) {
                    continue;
                }

                // a property or offset missing at a break is unknown there, not unset
                if ($type->possibly_undefined && !self::isLoopVar($var_id)) {
                    continue;
                }

                if (isset($inner_context->vars_in_scope[$var_id])) {
                    $type = $type->addParentNodes($inner_context->vars_in_scope[$var_id]->parent_nodes);
                }

                if (isset($context->vars_in_scope[$var_id])) {
                    $type = $type->addParentNodes($context->vars_in_scope[$var_id]->parent_nodes);
                }

                $context->vars_in_scope[$var_id] = $type;
            }

            return;
        }

        $has_break = in_array(ScopeAnalyzer::ACTION_BREAK, $loop_scope->final_actions, true);
        $has_continue = in_array(ScopeAnalyzer::ACTION_CONTINUE, $loop_scope->final_actions, true);

        foreach ($inner_context->vars_in_scope as $var_id => $type) {
            // if there are break statements in the loop it's not certain
            // that the loop has finished executing, so the assertions at the end
            // the loop in the while conditional may not hold.
            // This does not apply to variables assigned before the loop body
            // (e.g. a foreach key/value), which are guaranteed to be assigned at
            // the start of every iteration, even one that breaks or continues.
            if (($has_break || $has_continue)
                && !in_array($var_id, $always_assigned_before_loop_body_vars, true)
            ) {
                $break_type = $loop_scope->possibly_defined_loop_parent_vars[$var_id] ?? null;

                if ($break_type) {
                    if (isset($loop_scope->unknown_offsets[$var_id])
                        || (str_ends_with($var_id, ']')
                            && $type->possibly_undefined !== $break_type->possibly_undefined)
                    ) {
                        // an offset only some of the ways out of the loop know to be set: the type of the array
                        // tells
                        unset($context->vars_in_scope[$var_id], $context->vars_possibly_in_scope[$var_id]);
                    } else {
                        $context->vars_in_scope[$var_id] = Type::combineUnionTypes($type, $break_type);
                    }
                } elseif (!$has_break
                    && !$loop_scope->continues_through_finally
                    && isset($loop_scope->vars_at_continues[$var_id])
                ) {
                    // an iteration ends at the end of the body or at a continue: a variable defined at both is
                    // defined after the loop
                    $is_possibly_undefined = $type->possibly_undefined
                        || $loop_scope->vars_at_continues[$var_id]->possibly_undefined;

                    if (isset($loop_scope->possibly_defined_loop_vars[$var_id])) {
                        // the type already holds what the continues left, but a property or offset missing at a
                        // continue is unknown there, not unset
                        if (!$is_possibly_undefined || self::isLoopVar($var_id)) {
                            $context->vars_in_scope[$var_id] = $type->setPossiblyUndefined($is_possibly_undefined);
                        }
                    } elseif (!$is_possibly_undefined && isset($context->vars_in_scope[$var_id])) {
                        $context->vars_in_scope[$var_id] = $context->vars_in_scope[$var_id]
                            ->setPossiblyUndefined(false);
                    }
                }
            } else {
                $context->vars_in_scope[$var_id] = $type;
            }
        }
    }

    /**
     * @param array<string, Union> $init_var_types
     * @psalm-capabilities read-props
     */
    private static function doesEnterLoop(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\For_|PhpParser\Node\Stmt\While_ $stmt,
        array $init_var_types,
    ): bool {
        $always_enters_loop = false;

        if ($stmt instanceof PhpParser\Node\Stmt\While_) {
            if ($cond_type = $statements_analyzer->node_data->getType($stmt->cond)) {
                $always_enters_loop = $cond_type->isAlwaysTruthy();
            }

            return $always_enters_loop || (
                $stmt->cond instanceof PhpParser\Node\Expr\BinaryOp
                && self::isConditionTrueForInitValue($statements_analyzer, $stmt->cond, $init_var_types)
            );
        }

        foreach ($stmt->cond as $cond) {
            if ($cond_type = $statements_analyzer->node_data->getType($cond)) {
                $always_enters_loop = $cond_type->isAlwaysTruthy();
            }

            if (count($stmt->init) === 1
                && count($stmt->cond) === 1
                && $cond instanceof PhpParser\Node\Expr\BinaryOp
                && self::isConditionTrueForInitValue($statements_analyzer, $cond, $init_var_types)
            ) {
                $always_enters_loop = true;
                break;
            }
        }

        return $always_enters_loop;
    }

    /**
     * Whether `$i < 10`, `++$i <= 10`, `--$i > 0` ... holds the first time it is evaluated, as `$i` is a literal int
     * before the loop and the other side is a literal
     *
     * @param array<string, Union> $init_var_types
     * @psalm-capabilities read-props
     */
    private static function isConditionTrueForInitValue(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp $cond,
        array $init_var_types,
    ): bool {
        $cond_value = $statements_analyzer->node_data->getType($cond->right);
        // a non-numeric string is compared as a string since PHP 8, and as 0 before
        if (!$cond_value
            || !($cond_value->isSingleIntLiteral()
                || ($cond_value->isSingleStringLiteral() && is_numeric($cond_value->getSingleStringLiteral()->value)))
        ) {
            return false;
        }

        $variable = $cond->left;
        $adjustment = 0;
        if ($variable instanceof PhpParser\Node\Expr\PreInc) {
            $variable = $variable->var;
            $adjustment = 1;
        } elseif ($variable instanceof PhpParser\Node\Expr\PreDec) {
            $variable = $variable->var;
            $adjustment = -1;
        }

        if (!$variable instanceof PhpParser\Node\Expr\Variable
            || !is_string($variable->name)
            || !isset($init_var_types[$variable->name])
            || !$init_var_types[$variable->name]->isSingleIntLiteral()
        ) {
            return false;
        }

        $init_value = $init_var_types[$variable->name]->getSingleIntLiteral()->value + $adjustment;
        $cond_value = $cond_value->getSingleLiteral()->value;

        return match (true) {
            $cond instanceof PhpParser\Node\Expr\BinaryOp\Smaller => $init_value < $cond_value,
            $cond instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual => $init_value <= $cond_value,
            $cond instanceof PhpParser\Node\Expr\BinaryOp\Greater => $init_value > $cond_value,
            $cond instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual => $init_value >= $cond_value,
            default => false,
        };
    }

    /**
     * A variable the loop can define, rather than an array item, a property or a static property of one, or a
     * superglobal, which the loop only reads (and which is always defined)
     *
     * @psalm-pure
     */
    private static function isLoopVar(string $var_id): bool
    {
        return !str_contains($var_id, '[')
            && !str_contains($var_id, '->')
            && !str_contains($var_id, '::')
            && !VariableFetchAnalyzer::isSuperGlobal($var_id);
    }

    private static function updateLoopScopeContexts(
        LoopScope $loop_scope,
        Context $loop_context,
        Context $continue_context,
        Context $pre_outer_context,
    ): void {
        if (!in_array(ScopeAnalyzer::ACTION_CONTINUE, $loop_scope->final_actions, true)) {
            $loop_context->vars_in_scope = $pre_outer_context->vars_in_scope;
        } else {
            $updated_loop_vars = [];

            foreach ($loop_scope->redefined_loop_vars as $var => $type) {
                $continue_context->vars_in_scope[$var] = $type;
                $updated_loop_vars[$var] = true;
            }

            foreach ($loop_scope->possibly_redefined_loop_vars as $var => $type) {
                if ($continue_context->hasVariable($var)) {
                    if (!isset($updated_loop_vars[$var])) {
                        $continue_context->vars_in_scope[$var] = Type::combineUnionTypes(
                            $continue_context->vars_in_scope[$var],
                            $type,
                        );
                    } else {
                        $continue_context->vars_in_scope[$var] =
                            $continue_context->vars_in_scope[$var]->addParentNodes($type->parent_nodes);
                    }
                }
            }

            // variables defined in the loop body keep what a `continue` assigned them too
            foreach ($loop_scope->possibly_defined_loop_vars as $var => $type) {
                if ($continue_context->hasVariable($var)) {
                    $continue_context->vars_in_scope[$var] = Type::combineUnionTypes(
                        $continue_context->vars_in_scope[$var],
                        $type,
                    );
                }
            }
        }

        // merge vars possibly in scope at the end of each loop
        $loop_context->vars_possibly_in_scope = [
            ...$loop_context->vars_possibly_in_scope,
            ...$loop_scope->vars_possibly_in_scope,
        ];
    }

    /**
     * The variables a do-while condition changed belong to the context the loop is left with, with the type they
     * were given. The operands of `&&` after the first are skipped when an earlier one is false, so their
     * variables may keep the type they had at the end of the body.
     *
     * @param array<string, Union> $vars_after_condition
     */
    private static function applyVarsAfterCondition(
        Context $inner_do_context,
        array $vars_after_condition,
        bool $may_be_skipped,
    ): void {
        foreach ($vars_after_condition as $var_id => $type) {
            if (!$may_be_skipped) {
                $inner_do_context->vars_in_scope[$var_id] = $type;
            } elseif (isset($inner_do_context->vars_in_scope[$var_id])) {
                $inner_do_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                    $inner_do_context->vars_in_scope[$var_id],
                    $type,
                );
            } else {
                $inner_do_context->vars_in_scope[$var_id] = $type->setPossiblyUndefined(true);
            }
        }
    }

    /**
     * @param  list<Clause>  $pre_condition_clauses the formula of the condition, empty when it doesn't decide whether
     *                                              the loop goes on: built again once the condition is analysed, since
     *                                              what it says can depend on the types of its operands (`$i < $max`)
     * @param  array<string, Union> $vars_after_condition the variables the condition changes, for a do-while
     * @param  array<string, Union> $written_var_ids gets the variables evaluating the condition writes, with what they
     *                                               hold once it is evaluated
     * @return list<string>
     */
    private static function applyPreConditionToLoopContext(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $pre_condition,
        array &$pre_condition_clauses,
        Context $loop_context,
        Context $outer_context,
        bool $is_do,
        array &$vars_after_condition = [],
        array &$written_var_ids = [],
    ): array {
        $vars_before_condition = $loop_context->vars_in_scope;
        $pre_referenced_var_ids = $loop_context->cond_referenced_var_ids;
        $loop_context->cond_referenced_var_ids = [];

        $was_inside_conditional = $loop_context->inside_conditional;

        $loop_context->inside_conditional = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $pre_condition, $loop_context) === false) {
            $loop_context->inside_conditional = $was_inside_conditional;

            return [];
        }

        $loop_context->inside_conditional = $was_inside_conditional;

        if ($pre_condition_clauses !== []) {
            // not cached: the types of the operands can change from one pass to the next
            $pre_condition_clauses = FormulaGenerator::getFormula(
                spl_object_id($pre_condition),
                spl_object_id($pre_condition),
                $pre_condition,
                $loop_context->self,
                $statements_analyzer,
                $statements_analyzer->getCodebase(),
                cache: false,
            );
        }

        // the variables as the condition leaves them, before the truths of the condition narrow them
        if ($is_do) {
            foreach ($loop_context->vars_in_scope as $var_id => $type) {
                if (!isset($vars_before_condition[$var_id]) || $vars_before_condition[$var_id] !== $type) {
                    $vars_after_condition[$var_id] = $type;
                }
            }
        }

        // a variable is assigned at the position of the assignment; what it holds once the condition is evaluated,
        // before the condition is assumed true, is what the loop is left with, whichever pass evaluated it
        $condition_start = (int) $pre_condition->getAttribute('startFilePos');
        $condition_end = (int) $pre_condition->getAttribute('endFilePos');

        foreach ($loop_context->assigned_var_ids as $written_var_id => $assigned_offset) {
            if ($assigned_offset >= $condition_start && $assigned_offset <= $condition_end) {
                foreach ($loop_context->vars_in_scope as $var_id => $type) {
                    if ($var_id === $written_var_id
                        || str_starts_with($var_id, $written_var_id . '[')
                        || str_starts_with($var_id, $written_var_id . '->')
                    ) {
                        $written_var_ids[$var_id] = Type::combineUnionTypes($written_var_ids[$var_id] ?? null, $type);
                    }
                }
            }
        }

        $new_referenced_var_ids = $loop_context->cond_referenced_var_ids;
        $loop_context->cond_referenced_var_ids = array_merge($pre_referenced_var_ids, $new_referenced_var_ids);

        $always_assigned_before_loop_body_vars = Context::getNewOrUpdatedVarIds($outer_context, $loop_context);

        $loop_context->clauses = Algebra::simplifyCNF(
            [...$outer_context->clauses, ...$pre_condition_clauses],
        );

        $active_while_types = [];

        $reconcilable_while_types = Algebra::getTruthsFromFormula(
            $loop_context->clauses,
            spl_object_id($pre_condition),
            $new_referenced_var_ids,
            $active_while_types,
        );

        $changed_var_ids = [];

        if ($reconcilable_while_types) {
            [$loop_context->vars_in_scope, $loop_context->references_in_scope] = Reconciler::reconcileKeyedTypes(
                $reconcilable_while_types,
                $active_while_types,
                $loop_context->vars_in_scope,
                $loop_context->references_in_scope,
                $changed_var_ids,
                $new_referenced_var_ids,
                $statements_analyzer,
                [],
                true,
                new CodeLocation($statements_analyzer->getSource(), $pre_condition),
            );
        }

        if ($is_do) {
            return [];
        }

        foreach ($always_assigned_before_loop_body_vars as $var_id) {
            $loop_context->clauses = Context::filterClauses(
                $var_id,
                $loop_context->clauses,
                null,
                $statements_analyzer,
            );
        }

        return $always_assigned_before_loop_body_vars;
    }

    /**
     * @param array<string, array<string, bool>>   $assignment_map
     * @psalm-pure
     */
    private static function getAssignmentMapDepth(string $first_var_id, array $assignment_map): int
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

    /**
     * What the variables hold where an iteration goes on to the next one: the end of the body, or only its
     * `continue`s when the body never gets to its end (`return`, `break`, `throw`), followed by the post
     * expressions of a `for`
     *
     * @param array<PhpParser\Node\Expr> $post_expressions
     * @return array<string, Union>
     */
    private static function getVarsStartingNextIteration(
        Context $continue_context,
        LoopScope $loop_scope,
        array $post_expressions,
    ): array {
        if (!$continue_context->has_returned) {
            return $continue_context->vars_in_scope;
        }

        if (!in_array(ScopeAnalyzer::ACTION_CONTINUE, $loop_scope->final_actions, true)) {
            return [];
        }

        return array_intersect_key(
            $continue_context->vars_in_scope,
            self::getAssignedVarIds($post_expressions, $continue_context->self),
        )
            + $loop_scope->possibly_redefined_loop_vars
            + $loop_scope->possibly_defined_loop_vars;
    }

    /**
     * @param array<PhpParser\Node\Expr> $exprs
     * @return array<string, true>
     */
    private static function getAssignedVarIds(array $exprs, ?string $this_class_name): array
    {
        if (!$exprs) {
            return [];
        }

        $assignment_mapper = new AssignmentMapVisitor($this_class_name);
        $traverser = new PhpParser\NodeTraverser;
        $traverser->addVisitor($assignment_mapper);
        $traverser->traverse($exprs);

        return $assignment_mapper->getAssignedVarIds();
    }

    /**
     * The ints a range shifted in a loop is widened to before it is unbounded: those the loop has as literals
     * (what it compares with, clamps to…), each with its neighbours to cover `<` and `<=`
     *
     * @param array<PhpParser\Node> $nodes
     * @return list<int>
     */
    private static function getWideningBounds(array $nodes): array
    {
        $bounds = [];

        foreach ((new NodeFinder())->findInstanceOf($nodes, PhpParser\Node\Scalar\Int_::class) as $int) {
            if ($int->value >= PHP_INT_MAX - 1) {
                continue;
            }

            foreach ([$int->value, -$int->value] as $value) {
                $bounds[$value - 1] = true;
                $bounds[$value] = true;
                $bounds[$value + 1] = true;
            }
        }

        $bounds = array_keys($bounds);
        sort($bounds);

        return $bounds;
    }

    /**
     * The variables whose int range at the end of an iteration, or that of an array value they hold, goes past the
     * ints it started it with, with that side moved to the next of the bounds, or unbounded
     *
     * @param array<string, Union> $vars_at_start
     * @param array<string, Union> $vars_at_end
     * @param ?list<int> $bounds sorted
     * @return array<string, Union>
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function getWidenedIntRanges(array $vars_at_start, array $vars_at_end, ?array $bounds): array
    {
        $widened_var_types = [];

        foreach ($vars_at_end as $var_id => $end_type) {
            if (!isset($vars_at_start[$var_id])) {
                continue;
            }

            $widened_type = self::widenIntRanges($vars_at_start[$var_id], $end_type, $bounds);

            if ($widened_type !== $end_type) {
                $widened_var_types[$var_id] = $widened_type;
            }
        }

        return $widened_var_types;
    }

    /**
     * Literals are left alone: they come from assignments, so the passes reach all of them.
     *
     * @param ?list<int> $bounds sorted
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function widenIntRanges(Union $start_type, Union $end_type, ?array $bounds): Union
    {
        $end_type = self::widenArrayValueIntRanges($start_type, $end_type, $bounds);

        $start_bounds = self::getIntBounds($start_type);

        if ($start_bounds === null) {
            return $end_type;
        }

        [$start_min, $start_max] = $start_bounds;

        $widen_min = false;
        $widen_max = false;

        foreach ($end_type->getAtomicTypes() as $atomic) {
            if (!$atomic instanceof TIntRange) {
                continue;
            }

            if ($start_min !== null && ($atomic->min_bound === null || $atomic->min_bound < $start_min)) {
                $widen_min = true;
            }

            if ($start_max !== null && ($atomic->max_bound === null || $atomic->max_bound > $start_max)) {
                $widen_max = true;
            }
        }

        if (!$widen_min && !$widen_max) {
            return $end_type;
        }

        [$end_min, $end_max] = self::getIntBounds($end_type) ?? [null, null];

        $types = array_filter(
            $end_type->getAtomicTypes(),
            static fn(Atomic $atomic): bool => !$atomic instanceof TInt,
        );

        $min = $start_min === null || $end_min === null ? null : min($start_min, $end_min);
        $max = $start_max === null || $end_max === null ? null : max($start_max, $end_max);

        if ($widen_min) {
            $min = $min === null || $bounds === null ? null : self::getBoundBeyond($bounds, $min, false);
        }

        if ($widen_max) {
            $max = $max === null || $bounds === null ? null : self::getBoundBeyond($bounds, $max, true);
        }

        $types[] = $min === null && $max === null ? new TInt() : new TIntRange($min, $max);

        return $end_type->setTypes($types);
    }

    /**
     * @param ?list<int> $bounds sorted
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function widenArrayValueIntRanges(Union $start_type, Union $end_type, ?array $bounds): Union
    {
        $types = [];
        $changed = false;

        foreach ($end_type->getAtomicTypes() as $key => $atomic) {
            if ($atomic instanceof TKeyedArray && !$atomic->is_callable) {
                $properties = [];

                foreach ($atomic->properties as $offset => $property) {
                    $start_property = self::getArrayValueType($start_type, $offset);

                    $properties[$offset] = $start_property === null
                        ? $property
                        : self::widenIntRanges($start_property, $property, $bounds);
                }

                $fallback_params = $atomic->fallback_params;

                if ($fallback_params !== null) {
                    $start_value = self::getArrayValueType($start_type, null);

                    if ($start_value !== null) {
                        $fallback_params = [
                            $fallback_params[0],
                            self::widenIntRanges($start_value, $fallback_params[1], $bounds),
                        ];
                    }
                }

                if ($properties !== $atomic->properties || $fallback_params !== $atomic->fallback_params) {
                    $atomic = TKeyedArray::make(
                        $properties,
                        $atomic->class_strings,
                        $fallback_params,
                        $atomic->is_list,
                        $atomic->from_docblock,
                    );
                    $changed = true;
                }
            } elseif ($atomic instanceof TArray) {
                $start_value = self::getArrayValueType($start_type, null);

                if ($start_value !== null) {
                    $value = self::widenIntRanges($start_value, $atomic->type_params[1], $bounds);

                    if ($value !== $atomic->type_params[1]) {
                        $atomic = $atomic->setTypeParams([$atomic->type_params[0], $value]);
                        $changed = true;
                    }
                }
            }

            $types[$key] = $atomic;
        }

        return $changed ? $end_type->setTypes($types) : $end_type;
    }

    /**
     * What the arrays of a type hold at an offset, or at the offsets their shapes don't list for null; null if it
     * holds no array that has it
     *
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function getArrayValueType(Union $type, int|string|null $offset): ?Union
    {
        $value_type = null;

        foreach ($type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TKeyedArray) {
                if ($offset !== null && isset($atomic->properties[$offset])) {
                    $atomic_value_type = $atomic->properties[$offset];
                } elseif ($atomic->fallback_params !== null) {
                    $atomic_value_type = $atomic->fallback_params[1];
                } else {
                    continue;
                }
            } elseif ($atomic instanceof TArray) {
                $atomic_value_type = $atomic->type_params[1];
            } else {
                continue;
            }

            $value_type = Type::combineUnionTypes($value_type, $atomic_value_type);
        }

        return $value_type;
    }

    /**
     * The first of the sorted bounds at or past a value, upwards or downwards; null if there is none
     *
     * @param list<int> $bounds
     * @psalm-pure
     */
    private static function getBoundBeyond(array $bounds, int $value, bool $upwards): ?int
    {
        if (!$upwards) {
            $bounds = array_reverse($bounds);
        }

        foreach ($bounds as $bound) {
            if ($upwards ? $bound >= $value : $bound <= $value) {
                return $bound;
            }
        }

        return null;
    }

    /**
     * The lowest and highest of the ints a type holds, null for an unbounded side, or null if it holds no int
     *
     * @return ?array{?int, ?int}
     * @psalm-pure
     */
    private static function getIntBounds(Union $type): ?array
    {
        $bounds = null;

        foreach ($type->getAtomicTypes() as $atomic) {
            if (!$atomic instanceof TInt) {
                continue;
            }

            $range = TIntRange::convertToIntRange($atomic);

            if ($bounds === null) {
                $bounds = [$range->min_bound, $range->max_bound];

                continue;
            }

            $bounds = [
                $bounds[0] === null || $range->min_bound === null ? null : min($bounds[0], $range->min_bound),
                $bounds[1] === null || $range->max_bound === null ? null : max($bounds[1], $range->max_bound),
            ];
        }

        return $bounds;
    }

    /**
     * @param array<string, true> $feeding_var_ids
     * @param array<string, Union> $vars_at_start
     * @param array<string, Union> $vars_at_end
     * @return array<string, true>
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function getChangedFeedingVarIds(
        array $feeding_var_ids,
        array $vars_at_start,
        array $vars_at_end,
    ): array {
        $changed_var_ids = [];

        foreach ($feeding_var_ids as $var_id => $_) {
            if (!isset($vars_at_end[$var_id])) {
                continue;
            }

            if (!isset($vars_at_start[$var_id])
                || self::differ(
                    Type::combineUnionTypes($vars_at_start[$var_id], $vars_at_end[$var_id]),
                    $vars_at_start[$var_id],
                )
            ) {
                $changed_var_ids[$var_id] = true;
            }
        }

        return $changed_var_ids;
    }

    /**
     * Types that read the same may still differ: closures made from different functions or methods do
     *
     * @psalm-pure
     */
    private static function differ(Union $type, Union $other_type): bool
    {
        return $type->getId() !== $other_type->getId()
            || array_diff_key($type->getAtomicTypes(), $other_type->getAtomicTypes()) !== []
            || array_diff_key($other_type->getAtomicTypes(), $type->getAtomicTypes()) !== [];
    }
}
