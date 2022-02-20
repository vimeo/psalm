<?php

namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use PhpParser\Node\Stmt\Catch_;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Scope\TryCatchScope;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\InvalidCatch;
use Psalm\Issue\ParseError;
use Psalm\Issue\RedundantCatch;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function array_column;
use function array_merge;
use function array_values;
use function assert;
use function count;
use function in_array;
use function is_string;
use function strpos;
use function strtolower;

/**
 * @internal
 */
class TryAnalyzer
{
    /**
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\TryCatch $stmt,
        Context $context
    ): ?bool {
        $catch_actions = [];
        $all_catches_leave = true;

        $codebase = $statements_analyzer->getCodebase();

        /** @var int $i */
        foreach ($stmt->catches as $i => $catch) {
            $catch_actions[$i] = ScopeAnalyzer::getControlActions(
                $catch->stmts,
                $statements_analyzer->node_data,
                []
            );
            $all_catches_leave = $all_catches_leave && !in_array(ScopeAnalyzer::ACTION_NONE, $catch_actions[$i], true);
        }

        $existing_thrown_exceptions = $context->possibly_thrown_exceptions;

        /**
         * @var array<string, array<array-key, CodeLocation>> $context->possibly_thrown_exceptions
         */
        $context->possibly_thrown_exceptions = [];

        $old_context = clone $context;

        $old_try_catch_scope = $context->try_catch_scope;
        $context->try_catch_scope = new TryCatchScope();

        if (!$all_catches_leave || $stmt->finally) {
            if ($codebase->alter_code) {
                // TODO can this be moved?
                $context->branch_point = $context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
            }
        }

        $assigned_var_ids = $context->assigned_var_ids;
        $context->assigned_var_ids = [];

        if ($statements_analyzer->analyze($stmt->stmts, $context) === false) {
            $context->try_catch_scope = $old_try_catch_scope;
            return false;
        }

        $end_of_try_context = clone $context;
        $end_of_try_context->cloneVarsInScope();

        // Now that the `try` has been analyzed, we have to update the context under the
        // assumption that an exception may have been thrown at any point within the `try`.
        self::updateContextWithTryAssignments($context, $old_context, $codebase);

        if ($old_try_catch_scope !== null) {
            $old_try_catch_scope->applyInnerScope($context->try_catch_scope);
        }
        $context->try_catch_scope = $old_try_catch_scope;

        $context->has_returned = false;

        /** @var array<string, int> */
        $newly_assigned_var_ids = $context->assigned_var_ids;

        $context->assigned_var_ids = array_merge(
            $assigned_var_ids,
            $newly_assigned_var_ids
        );

        $try_leaves_loop = $context->loop_scope
            && $context->loop_scope->final_actions
            && !in_array(ScopeAnalyzer::ACTION_NONE, $context->loop_scope->final_actions, true);

        // Maps catch block number to list of tuple(caught exception, CodeLocation for that exception)
        $caught_types = [];
        $has_universal_catch = false;
        // array<int, true> set of leaving catches
        $leaving_catches = [];
        $catch_contexts = [];
        $i = -1;
        /** @var int<0, max> $i */
        foreach ($stmt->catches as $i => $catch) {
            $catch_context = self::analyzeCatch(
                $context,
                $statements_analyzer,
                $catch,
                $has_universal_catch,
                $caught_types,
                $i,
            );
            $catch_contexts[$i] = $catch_context;

            // recalculate in case there's a no-return clause
            $catch_actions[$i] = ScopeAnalyzer::getControlActions(
                $catch->stmts,
                $statements_analyzer->node_data,
                [],
            );
            if (!in_array(ScopeAnalyzer::ACTION_NONE, $catch_actions[$i], true)) {
                $leaving_catches[$i] = true;
            }

            if ($catch_context->collect_exceptions) {
                $context->mergeExceptions($catch_context);
            }
        }
        $catch_block_count = $i + 1;
        $all_catches_leave = count($leaving_catches) === $catch_block_count;
        $vars_at_ends_of_catches = self::collectVarsFromCatchBlocks($catch_contexts);

        $try_block_control_actions = ScopeAnalyzer::getControlActions(
            $stmt->stmts,
            $statements_analyzer->node_data,
            [],
        );
        $try_leaves_scope = $try_block_control_actions === [ScopeAnalyzer::ACTION_END];

        $finally_has_returned = false;
        if ($stmt->finally !== null) {
            // The `finally` analysis is run twice, once with types set to what they'll
            // actually be for the `finally` scope, and again with issues ignored with types
            // set to what they'll be in the outer scope in order to update the outer scope.

            // Initial analysis with correct types.
            $finally_context = clone $context;
            $finally_context->cloneVarsInScope();
            self::applyCatchContextsForFinallyContext(
                $codebase,
                $finally_context,
                $end_of_try_context,
                $vars_at_ends_of_catches,
                $catch_block_count,
                $has_universal_catch,
            );
            $statements_analyzer->analyze($stmt->finally->stmts, $finally_context);
            $finally_has_returned = $finally_context->has_returned;
        }

        if ($context->loop_scope
            && !$try_leaves_loop
            && !in_array(ScopeAnalyzer::ACTION_NONE, $context->loop_scope->final_actions, true)
        ) {
            $context->loop_scope->final_actions[] = ScopeAnalyzer::ACTION_NONE;
        }

        self::applyCatchContextsForOuterContext(
            $codebase,
            $context,
            $end_of_try_context,
            $vars_at_ends_of_catches,
            $catch_block_count,
            $leaving_catches,
            $try_leaves_scope,
        );

        if ($stmt->finally !== null) {
            // Second `finally` analysis with types for the outer scope
            IssueBuffer::startRecording();
            $statements_analyzer->analyze($stmt->finally->stmts, $context);
            IssueBuffer::clearRecordingLevel();
            IssueBuffer::stopRecording();
        }

        foreach ($existing_thrown_exceptions as $possibly_thrown_exception => $codelocations) {
            foreach ($codelocations as $hash => $codelocation) {
                $context->possibly_thrown_exceptions[$possibly_thrown_exception][$hash] = $codelocation;
            }
        }

        $body_has_returned = !in_array(ScopeAnalyzer::ACTION_NONE, $try_block_control_actions, true);
        $context->has_returned = ($body_has_returned && $all_catches_leave) || $finally_has_returned;

        // Remove all newly memoized properties and method calls from the context
        foreach ($context->vars_in_scope as $var_id => $var_type) {
            if (!isset($context->vars_in_scope[$var_id])) {
                continue; // Removed as descendant in previous iteration
            }

            if (strpos($var_id, "->") !== false) {
                // Set memoized type as always defined
                $var_type->possibly_undefined = $var_type->possibly_undefined_from_try = false;

                if (!isset($old_context->vars_in_scope[$var_id])
                    || !UnionTypeComparator::isContainedBy($codebase, $var_type, $old_context->vars_in_scope[$var_id])
                    || !UnionTypeComparator::isContainedBy($codebase, $old_context->vars_in_scope[$var_id], $var_type)
                ) {
                    // If it doesn't exist in the outer scope or the type has changed, remove it
                    $context->remove($var_id);
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, non-empty-list<array{Union, CodeLocation}>> $caught_types Maps catch block number to list of
     *                                                                             caught types and their associated
     *                                                                             CodeLocations.
     */
    private static function analyzeCatch(
        Context $before_catch_context,
        StatementsAnalyzer $statements_analyzer,
        Catch_ $catch,
        bool &$has_universal_catch,
        array &$caught_types,
        int $catch_number
    ): Context {
        $codebase = $statements_analyzer->getCodebase();

        $catch_context = clone $before_catch_context;
        $catch_context->cloneVarsInScope();
        $catch_context->has_returned = false;
        $catch_context->try_catch_scope = new TryCatchScope();

        $fq_catch_classes = [];

        assert(!empty($catch->types));
        foreach ($catch->types as $catch_type_stmt) {
            $fq_catch_class = ClassLikeAnalyzer::getFQCLNFromNameObject(
                $catch_type_stmt,
                $statements_analyzer->getAliases()
            );

            $fq_catch_class = $codebase->classlikes->getUnAliasedName($fq_catch_class);

            if ($codebase->alter_code && $fq_catch_class) {
                $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $statements_analyzer,
                    $catch_type_stmt,
                    $fq_catch_class,
                    $catch_context->calling_method_id
                );
            }

            if ($catch_context->check_classes) {
                if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $statements_analyzer,
                    $fq_catch_class,
                    new CodeLocation(
                        $statements_analyzer->getSource(),
                        $catch_type_stmt,
                        $catch_context->include_location,
                    ),
                    $catch_context->self,
                    $catch_context->calling_method_id,
                    $statements_analyzer->getSuppressedIssues(),
                    new ClassLikeNameOptions(true)
                ) === false) {
                    // fall through
                }
            }

            $caught_atomic = new TNamedObject($fq_catch_class);

            if (($codebase->classExists($fq_catch_class)
                    && strtolower($fq_catch_class) !== 'exception'
                    && !($codebase->classExtends($fq_catch_class, 'Exception')
                        || $codebase->classImplements($fq_catch_class, 'Throwable')))
                || ($codebase->interfaceExists($fq_catch_class)
                    && strtolower($fq_catch_class) !== 'throwable'
                    && !$codebase->interfaceExtends($fq_catch_class, 'Throwable'))
            ) {
                IssueBuffer::maybeAdd(
                    new InvalidCatch(
                        'Class/interface ' . $fq_catch_class . ' cannot be caught',
                        new CodeLocation($statements_analyzer->getSource(), $catch),
                        $fq_catch_class
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
                // Since we already show an InvalidCatch, we just assume that it's a Throwable from here on out.
                $caught_atomic->addIntersectionType(new TNamedObject('Throwable'));
            }

            $has_universal_catch = $has_universal_catch || strtolower($fq_catch_class) === "throwable";

            // Handle redundant catches
            $caught_type = new Union([$caught_atomic]);
            $caught_location = new CodeLocation($statements_analyzer->getSource(), $catch_type_stmt);
            foreach ($caught_types as $already_caught_block => $caught_types_list) {
                foreach ($caught_types_list as [$already_caught, $already_caught_location]) {
                    if (UnionTypeComparator::isContainedBy(
                        $codebase,
                        $caught_type,
                        $already_caught,
                    )) {
                        IssueBuffer::maybeAdd(
                            new RedundantCatch(
                                "{$caught_type->getId()} has already been caught",
                                $caught_location,
                                $caught_type->getId(),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    } elseif ($already_caught_block === $catch_number && UnionTypeComparator::isContainedBy(
                        $codebase,
                        $already_caught,
                        $caught_type,
                    )) {
                        // If this is the same catch block it's still redundant even if the wider type appears later
                        IssueBuffer::maybeAdd(
                            new RedundantCatch(
                                "{$already_caught->getId()} has already been caught",
                                $already_caught_location,
                                $already_caught->getId(),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }
                }
            }
            $caught_types[$catch_number][] = [$caught_type, $caught_location];

            $fq_catch_classes[] = $fq_catch_class;
        }

        if ($catch_context->collect_exceptions) {
            foreach ($fq_catch_classes as $fq_catch_class) {
                $fq_catch_class_lower = strtolower($fq_catch_class);

                foreach ($catch_context->possibly_thrown_exceptions as $exception_fqcln => $_) {
                    $exception_fqcln_lower = strtolower($exception_fqcln);

                    if ($exception_fqcln_lower === $fq_catch_class_lower
                        || ($codebase->classExists($exception_fqcln)
                            && $codebase->classExtendsOrImplements($exception_fqcln, $fq_catch_class))
                        || ($codebase->interfaceExists($exception_fqcln)
                            && $codebase->interfaceExtends($exception_fqcln, $fq_catch_class))
                    ) {
                        unset($before_catch_context->possibly_thrown_exceptions[$exception_fqcln]);
                        unset($catch_context->possibly_thrown_exceptions[$exception_fqcln]);
                    }
                }
            }

            $catch_context->possibly_thrown_exceptions = [];
        }

        // discard all clauses because crazy stuff may have happened in try block
        $catch_context->clauses = [];

        if ($catch->var && is_string($catch->var->name)) {
            $catch_var_id = '$' . $catch->var->name;

            $catch_context->vars_in_scope[$catch_var_id] = Type::combineUnionTypeArray(
                array_column($caught_types[$catch_number], 0),
                $codebase,
            );

            // removes dependent vars from $catch_context
            $catch_context->removeDescendents(
                $catch_var_id,
                $catch_context->vars_in_scope[$catch_var_id],
                $catch_context->vars_in_scope[$catch_var_id],
                $statements_analyzer
            );

            $catch_context->vars_possibly_in_scope[$catch_var_id] = true;

            $location = new CodeLocation($statements_analyzer->getSource(), $catch->var);

            if (!$statements_analyzer->hasVariable($catch_var_id)) {
                $statements_analyzer->registerVariable(
                    $catch_var_id,
                    $location,
                    $catch_context->branch_point
                );
            } else {
                $statements_analyzer->registerVariableAssignment(
                    $catch_var_id,
                    $location
                );
            }

            if ($statements_analyzer->data_flow_graph) {
                $catch_var_node = DataFlowNode::getForAssignment($catch_var_id, $location);

                $catch_context->vars_in_scope[$catch_var_id]->parent_nodes = [
                    $catch_var_node->id => $catch_var_node
                ];
            }
        } elseif ($catch->var === null && $codebase->analysis_php_version_id < 8_00_00) {
            IssueBuffer::maybeAdd(
                new ParseError(
                    "Catch must have variable before PHP 8.0",
                    new CodeLocation($statements_analyzer->getSource(), $catch),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        $statements_analyzer->analyze($catch->stmts, $catch_context);

        if ($catch_context->collect_exceptions) {
            $before_catch_context->mergeExceptions($catch_context);
        }

        if ($before_catch_context->try_catch_scope !== null) {
            $before_catch_context->try_catch_scope->applyInnerScope($catch_context->try_catch_scope);
        }

        return $catch_context;
    }

    /**
     * Update a context that has a try_catch_scope to combine assignments from a `try` scope.
     */
    private static function updateContextWithTryAssignments(
        Context $context,
        Context $outer_context,
        Codebase $codebase
    ): void {
        assert($context->try_catch_scope !== null);

        // Reset $vars_in_scope to outer scope, in case any types have been
        // reconciled (see TryCatchTest typeDoesNotContainTypeInCatch).
        $context->vars_in_scope = $outer_context->vars_in_scope;
        $context->references_in_scope = $outer_context->references_in_scope;

        foreach ($context->try_catch_scope->assignments_from_scope as $var_id => $assigned_types) {
            // All assignments in the `try` are unioned together and combined with the original type
            if (isset($outer_context->vars_in_scope[$var_id])) {
                $assigned_types[] = clone $outer_context->vars_in_scope[$var_id];
                $possibly_undefined_from_try = false;
            } else {
                // Variable didn't exist before try
                $possibly_undefined_from_try = true;
            }
            $context->vars_in_scope[$var_id] = Type::combineUnionTypeArray($assigned_types, $codebase);
            $context->vars_in_scope[$var_id]->possibly_undefined =
                $possibly_undefined_from_try ?: $outer_context->vars_in_scope[$var_id]->possibly_undefined;
            $context->vars_in_scope[$var_id]->possibly_undefined_from_try =
                $possibly_undefined_from_try ?: $outer_context->vars_in_scope[$var_id]->possibly_undefined_from_try;
        }
        foreach ($context->try_catch_scope->unset_from_scope as $var_id => $_) {
            if (!isset($context->vars_in_scope[$var_id])) {
                // If the variable no longer exists (ie it wasn't reassigned
                // after being unset), reset it to its old value.
                $context->vars_in_scope[$var_id] = clone $outer_context->vars_in_scope[$var_id];
            }
            $context->vars_in_scope[$var_id]->possibly_undefined = true;
            $context->vars_in_scope[$var_id]->possibly_undefined_from_try = true;
        }
    }

    /**
     * Collects variable types from `catch` blocks into a format that's easier to use.
     *
     * @param iterable<int<0, max>, Context> $catch_contexts
     *
     * @return array<string, array<int<0, max>, Union>>
     */
    private static function collectVarsFromCatchBlocks(iterable $catch_contexts): array
    {
        $vars_at_ends_of_catches = [];
        foreach ($catch_contexts as $catch_number => $catch_context) {
            foreach ($catch_context->vars_in_scope as $var_id => $var_type) {
                $vars_at_ends_of_catches[$var_id][$catch_number] = $var_type;
            }
        }
        return $vars_at_ends_of_catches;
    }

    /**
     * Applies variables from `catch` blocks to the current context in preparation for the `finally` block.
     *
     * @param array<string, array<int<0, max>, Union>> $vars_at_ends_of_catches
     */
    private static function applyCatchContextsForFinallyContext(
        Codebase $codebase,
        Context $context,
        Context $end_of_try_context,
        array $vars_at_ends_of_catches,
        int $catch_block_count,
        bool $has_universal_catch
    ): void {
        // Update context based on types from `catch` blocks.
        foreach ($vars_at_ends_of_catches as $var_id => $catch_types) {
            // Check if variable is always defined in `catch` blocks, including those that leave the scope.
            $always_defined_in_catches = count($catch_types) === $catch_block_count;
            if ($always_defined_in_catches) {
                foreach ($catch_types as $type) {
                    if ($type->possibly_undefined) {
                        $always_defined_in_catches = false;
                        break;
                    }
                }
            }

            // Union all catch types
            $types = array_values($catch_types);

            if ($always_defined_in_catches) {
                if ($has_universal_catch) {
                    // Since the variable is always defined in the `catch` blocks, it's
                    // either the type at the end of the `try` or one of the `catch` types.
                    $previous_type = $end_of_try_context->vars_in_scope[$var_id] ?? null;
                } else {
                    // Since there isn't a universal `catch`, the `finally` context can
                    // still have the variable with a type from any point within the `try`.
                    $previous_type = $context->vars_in_scope[$var_id] ?? null;
                }
                $possibly_undefined = $previous_type->possibly_undefined ?? true;
            } else {
                $previous_type = $context->vars_in_scope[$var_id] ?? null;
                $possibly_undefined = true;
            }
            if ($previous_type !== null) {
                $types[] = $previous_type;
            }

            if (count($types) === 0) {
                $context->remove($var_id);
                continue;
            }

            $context->vars_in_scope[$var_id] = Type::combineUnionTypeArray($types, $codebase);
            $context->vars_in_scope[$var_id]->possibly_undefined = $possibly_undefined;
            $context->vars_in_scope[$var_id]->possibly_undefined_from_try = false;
            $context->vars_possibly_in_scope[$var_id] = true;
        }
    }

    /**
     * Applies variables from `catch` blocks to the current context.
     *
     * @param array<string, array<int<0, max>, Union>> $vars_at_ends_of_catches
     * @param array<int, true> $leaving_catches
     */
    private static function applyCatchContextsForOuterContext(
        Codebase $codebase,
        Context $context,
        Context $end_of_try_context,
        array $vars_at_ends_of_catches,
        int $catch_block_count,
        array $leaving_catches,
        bool $try_leaves_scope
    ): void {
        if ($catch_block_count > 0) {
            // Update context based on types from `catch` blocks.
            foreach ($vars_at_ends_of_catches as $var_id => $catch_types) {
                // Check if variable is defined in all non-leaving `catch` blocks
                $always_defined_in_catches = count($catch_types + $leaving_catches) === $catch_block_count;
                if ($always_defined_in_catches) {
                    foreach ($catch_types as $catch_number => $type_from_catch) {
                        if (isset($leaving_catches[$catch_number]) || !$type_from_catch->possibly_undefined) {
                            continue;
                        }
                        $always_defined_in_catches = false;
                        break;
                    }
                }

                // Only union types from non-leaving catches
                $types = [];
                foreach ($catch_types as $catch_number => $type_from_catch) {
                    if (isset($leaving_catches[$catch_number])) {
                        continue;
                    }
                    $types[] = $type_from_catch;
                }

                if ($always_defined_in_catches) {
                    if ($try_leaves_scope) {
                        // Discard the type from the outer context and the `try` block and only use `catch` block types
                        $previous_type = null;
                        $possibly_undefined = false;
                    } else {
                        // Since the variable is always defined in the `catch` blocks, it's
                        // either the type at the end of the `try` or one of the `catch` types.
                        $previous_type = $end_of_try_context->vars_in_scope[$var_id] ?? null;
                        $possibly_undefined = $previous_type->possibly_undefined ?? true;
                    }
                } else {
                    $previous_type = $context->vars_in_scope[$var_id] ?? null;
                    $possibly_undefined = true;
                }
                if ($previous_type !== null) {
                    $types[] = $previous_type;
                }

                if (count($types) === 0) {
                    $context->remove($var_id);
                    continue;
                }

                $context->vars_in_scope[$var_id] = Type::combineUnionTypeArray($types, $codebase);
                $context->vars_in_scope[$var_id]->possibly_undefined = $possibly_undefined;
                $context->vars_in_scope[$var_id]->possibly_undefined_from_try = false;
                $context->vars_possibly_in_scope[$var_id] = true;
            }

            if (count($leaving_catches) < $catch_block_count) {
                // Check for variables unset in every `catch` block unless all `catch` blocks leave the scope
                foreach ($context->vars_in_scope as $var_id => $_) {
                    if (!isset($vars_at_ends_of_catches[$var_id])) {
                        $context->vars_in_scope[$var_id]->possibly_undefined = true;
                    }
                }
            }
        } else {
            // No catch blocks
            // Set all variables to their types at the end of the `try` block, since
            // it would have to complete successfully for execution to continue.
            $context->vars_in_scope = $end_of_try_context->vars_in_scope;
            $context->vars_possibly_in_scope = $end_of_try_context->vars_possibly_in_scope;
            $context->references_in_scope = $end_of_try_context->references_in_scope;
        }
    }
}
