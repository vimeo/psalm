<?php

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Type;

use function end;

/**
 * @internal
 */
class BreakAnalyzer
{
    public static function analyze(
        PhpParser\Node\Stmt\Break_ $stmt,
        Context $context
    ): void {
        $loop_scope = $context->loop_scope;

        $leaving_switch = true;

        if ($loop_scope) {
            if ($context->break_types
                && end($context->break_types) === 'switch'
                && (!$stmt->num instanceof PhpParser\Node\Scalar\LNumber || $stmt->num->value < 2)
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
                    $loop_scope->possibly_redefined_loop_parent_vars[$var] ?? null
                );
            }

            if ($loop_scope->iteration_count === 0) {
                foreach ($context->vars_in_scope as $var_id => $type) {
                    if (!isset($loop_scope->loop_parent_context->vars_in_scope[$var_id])) {
                        $loop_scope->possibly_defined_loop_parent_vars[$var_id] = Type::combineUnionTypes(
                            $type,
                            $loop_scope->possibly_defined_loop_parent_vars[$var_id] ?? null
                        );
                    }
                }
            }
        }

        $case_scope = $context->case_scope;
        if ($case_scope && $leaving_switch) {
            foreach ($context->vars_in_scope as $var_id => $type) {
                if ($case_scope->parent_context !== $context) {
                    if ($case_scope->break_vars === null) {
                        $case_scope->break_vars = [];
                    }

                    $case_scope->break_vars[$var_id] = Type::combineUnionTypes(
                        $type,
                        $case_scope->break_vars[$var_id] ?? null
                    );
                }
            }
        }

        $context->has_returned = true;
    }
}
