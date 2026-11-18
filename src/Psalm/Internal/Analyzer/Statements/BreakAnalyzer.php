<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\ContinueOutsideLoop;
use Psalm\IssueBuffer;
use Psalm\Type;

use function array_filter;
use function array_slice;
use function count;
use function str_ends_with;

/**
 * @internal
 */
final class BreakAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Break_ $stmt,
        Context $context,
    ): void {
        if ($context->break_types
            && $stmt->num instanceof PhpParser\Node\Scalar\Int_
            && $stmt->num->value > count($context->break_types)
        ) {
            IssueBuffer::maybeAdd(
                new ContinueOutsideLoop(
                    'Cannot \'break\' ' . $stmt->num->value . ' levels, only ' . count($context->break_types)
                        . ' enclosing loop or switch structure(s)',
                    new CodeLocation($statements_analyzer, $stmt),
                ),
                $statements_analyzer->getSource()->getSuppressedIssues(),
            );
        }

        $loop_scope = $context->loop_scope;

        $break_depth = $stmt->num instanceof PhpParser\Node\Scalar\Int_ ? $stmt->num->value : 1;
        $target_index = count($context->break_types) - $break_depth;
        $inner_break_types = $target_index >= 0 ? array_slice($context->break_types, $target_index + 1) : [];
        $loops_between = count(array_filter(
            $inner_break_types,
            static fn(string $break_type): bool => $break_type === 'loop',
        ));
        $targets_switch = $target_index >= 0 && $context->break_types[$target_index] === 'switch';

        $leaving_switch = true;

        if ($loop_scope) {
            if ($targets_switch && $loops_between === 0) {
                $loop_scope->final_actions[] = ScopeAnalyzer::ACTION_LEAVE_SWITCH;
            } else {
                $leaving_switch = false;

                $loop_scope->final_actions[] = ScopeAnalyzer::ACTION_BREAK;
            }

            // a `break 2` or more out of a nested loop does not land after this loop: it leaves the loops around it too
            $ends_this_loop = !$leaving_switch && $loops_between === 0;

            // a break out of a switch only reaches the end of the switch, which keeps what every case read
            if (!$leaving_switch) {
                $loop_scope->addJump($context);
            }

            if ($ends_this_loop) {
                $loop_scope->addBreak($context);
            }

            $redefined_vars = $context->getRedefinedVars($loop_scope->loop_parent_context->vars_in_scope);

            foreach ($redefined_vars as $var => $type) {
                $loop_scope->possibly_redefined_loop_parent_vars[$var] = Type::combineUnionTypes(
                    $type,
                    $loop_scope->possibly_redefined_loop_parent_vars[$var] ?? null,
                );
            }

            if ($loop_scope->iteration_count === 0) {
                // a variable a break out of the loop leaves undefined is only possibly defined after the loop
                foreach ($loop_scope->possibly_defined_loop_parent_vars as $var_id => $type) {
                    if ($ends_this_loop && !isset($context->vars_in_scope[$var_id])) {
                        $loop_scope->possibly_defined_loop_parent_vars[$var_id] = $type->setPossiblyUndefined(true);

                        if (str_ends_with($var_id, ']')) {
                            $loop_scope->unknown_offsets[$var_id] = true;
                        }
                    }
                }

                foreach ($context->vars_in_scope as $var_id => $type) {
                    if (!isset($loop_scope->loop_parent_context->vars_in_scope[$var_id])) {
                        $previous_type = $loop_scope->possibly_defined_loop_parent_vars[$var_id] ?? null;

                        if (str_ends_with($var_id, ']')
                            && ($previous_type
                                ? $previous_type->possibly_undefined !== $type->possibly_undefined
                                : $ends_this_loop && $loop_scope->break_count > 0)
                        ) {
                            $loop_scope->unknown_offsets[$var_id] = true;
                        }

                        $loop_scope->possibly_defined_loop_parent_vars[$var_id] = Type::combineUnionTypes(
                            $ends_this_loop && $loop_scope->break_count > 0 && $previous_type === null
                                ? $type->setPossiblyUndefined(true)
                                : $type,
                            $previous_type,
                        );
                    }
                }

                if ($ends_this_loop) {
                    ++$loop_scope->break_count;
                }
            }

            // `break 2` or more also leaves the loops around the innermost one, up to the one it targets
            if ($break_depth > 1
                && $target_index >= 0
                && $context->break_types[$target_index] === 'loop'
            ) {
                $target_scope = $loop_scope;

                for ($i = 0; $i < $loops_between && $target_scope; $i++) {
                    $target_scope = $target_scope->loop_parent_context->loop_scope;
                }

                if ($target_scope && $target_scope !== $loop_scope) {
                    $target_scope->final_actions[] = ScopeAnalyzer::ACTION_BREAK;
                    $target_scope->addJump($context);
                    $target_scope->addBreak($context);

                    $target_redefined_vars = $context->getRedefinedVars(
                        $target_scope->loop_parent_context->vars_in_scope,
                    );

                    foreach ($target_redefined_vars as $var => $type) {
                        $target_scope->possibly_redefined_loop_parent_vars[$var] = Type::combineUnionTypes(
                            $type,
                            $target_scope->possibly_redefined_loop_parent_vars[$var] ?? null,
                        );
                    }

                    if ($target_scope->iteration_count === 0) {
                        foreach ($context->vars_in_scope as $var_id => $type) {
                            if (!isset($target_scope->loop_parent_context->vars_in_scope[$var_id])) {
                                $target_scope->possibly_defined_loop_parent_vars[$var_id] = Type::combineUnionTypes(
                                    $type,
                                    $target_scope->possibly_defined_loop_parent_vars[$var_id] ?? null,
                                );
                            }
                        }
                    }
                }
            }

            if ($context->finally_scope) {
                foreach ($context->vars_in_scope as $var_id => $type) {
                    if (isset($context->finally_scope->vars_in_scope[$var_id])) {
                        $context->finally_scope->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $context->finally_scope->vars_in_scope[$var_id],
                            $type,
                            $statements_analyzer->getCodebase(),
                        );
                    } else {
                        $context->finally_scope->vars_in_scope[$var_id] = $type->setPossiblyUndefined(true, true);
                    }
                }
            }
        }

        // the case of the switch the break leaves, which a `break 2` or more reaches past the inner switches
        $case_scope = null;

        if ($targets_switch) {
            $case_scope = $context->case_scope;

            foreach ($inner_break_types as $break_type) {
                if ($break_type === 'switch') {
                    $case_scope = $case_scope?->outer_case_scope;
                }
            }
        }

        if ($case_scope) {
            $case_scope->addBreak($context);

            $is_first_break = $case_scope->break_vars === null;
            $case_scope->break_vars ??= [];

            // a variable that another break of the case has and this one lacks (or the other way around) is only
            // possibly defined where the switch is left
            foreach ($case_scope->break_vars as $var_id => $type) {
                if (!isset($context->vars_in_scope[$var_id])) {
                    $case_scope->break_vars[$var_id] = $type->setPossiblyUndefined(true);
                }
            }

            foreach ($context->vars_in_scope as $var_id => $type) {
                $case_scope->break_vars[$var_id] = Type::combineUnionTypes(
                    $is_first_break || isset($case_scope->break_vars[$var_id])
                        ? $type
                        : $type->setPossiblyUndefined(true),
                    $case_scope->break_vars[$var_id] ?? null,
                );
            }
        }

        $context->has_returned = true;
    }
}
