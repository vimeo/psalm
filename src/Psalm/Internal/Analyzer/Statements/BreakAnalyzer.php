<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Type;

use function array_filter;
use function array_slice;
use function count;
use function end;

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
        $loop_scope = $context->loop_scope;

        $leaving_switch = true;

        if ($loop_scope) {
            if ($context->break_types
                && end($context->break_types) === 'switch'
                && (!$stmt->num instanceof PhpParser\Node\Scalar\Int_ || $stmt->num->value < 2)
            ) {
                $loop_scope->final_actions[] = ScopeAnalyzer::ACTION_LEAVE_SWITCH;
            } else {
                $leaving_switch = false;

                $loop_scope->final_actions[] = ScopeAnalyzer::ACTION_BREAK;
            }

            $redefined_vars = $context->getRedefinedVars($loop_scope->loop_parent_context->vars_in_scope);

            foreach ($redefined_vars as $var => $type) {
                $loop_scope->possibly_redefined_loop_parent_vars[$var] = Type::combineUnionTypes(
                    $type,
                    $loop_scope->possibly_redefined_loop_parent_vars[$var] ?? null,
                );
            }

            if ($loop_scope->iteration_count === 0) {
                foreach ($context->vars_in_scope as $var_id => $type) {
                    if (!isset($loop_scope->loop_parent_context->vars_in_scope[$var_id])) {
                        $loop_scope->possibly_defined_loop_parent_vars[$var_id] = Type::combineUnionTypes(
                            $type,
                            $loop_scope->possibly_defined_loop_parent_vars[$var_id] ?? null,
                        );
                    }
                }
            }

            // `break 2` or more also leaves the loops around the innermost one, up to the one it targets
            $break_depth = $stmt->num instanceof PhpParser\Node\Scalar\Int_ ? $stmt->num->value : 1;
            $target_index = count($context->break_types) - $break_depth;

            if ($break_depth > 1
                && $target_index >= 0
                && $context->break_types[$target_index] === 'loop'
            ) {
                $loops_between = count(array_filter(
                    array_slice($context->break_types, $target_index + 1),
                    static fn(string $break_type): bool => $break_type === 'loop',
                ));

                $target_scope = $loop_scope;

                for ($i = 0; $i < $loops_between && $target_scope; $i++) {
                    $target_scope = $target_scope->loop_parent_context->loop_scope;
                }

                if ($target_scope && $target_scope !== $loop_scope) {
                    $target_scope->final_actions[] = ScopeAnalyzer::ACTION_BREAK;

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
                foreach ($context->vars_in_scope as $var_id => &$type) {
                    if (isset($context->finally_scope->vars_in_scope[$var_id])) {
                        $context->finally_scope->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $context->finally_scope->vars_in_scope[$var_id],
                            $type,
                            $statements_analyzer->getCodebase(),
                        );
                    } else {
                        $type = $type->setPossiblyUndefined(true, true);
                        $context->finally_scope->vars_in_scope[$var_id] = $type;
                    }
                }
                unset($type);
            }
        }

        $case_scope = $context->case_scope;
        if ($case_scope && $leaving_switch) {
            foreach ($context->vars_in_scope as $var_id => $type) {
                if ($case_scope->break_vars === null) {
                    $case_scope->break_vars = [];
                }

                $case_scope->break_vars[$var_id] = Type::combineUnionTypes(
                    $type,
                    $case_scope->break_vars[$var_id] ?? null,
                );
            }
        }

        $context->has_returned = true;
    }
}
