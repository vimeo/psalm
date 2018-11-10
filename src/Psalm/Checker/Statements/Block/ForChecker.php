<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\ScopeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Context;
use Psalm\Scope\LoopScope;
use Psalm\Type;

class ForChecker
{
    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Stmt\For_    $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\For_ $stmt,
        Context $context
    ) {
        $pre_assigned_var_ids = $context->assigned_var_ids;
        $context->assigned_var_ids = [];

        foreach ($stmt->init as $init) {
            if (ExpressionChecker::analyze($statements_checker, $init, $context) === false) {
                return false;
            }
        }

        $assigned_var_ids = $context->assigned_var_ids;

        $context->assigned_var_ids = array_merge(
            $pre_assigned_var_ids,
            $assigned_var_ids
        );

        $while_true = !$stmt->cond && !$stmt->init && !$stmt->loop;

        $pre_context = null;

        if ($while_true) {
            $pre_context = clone $context;
        }

        $for_context = clone $context;

        $for_context->inside_loop = true;

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        if ($project_checker->alter_code) {
            $for_context->branch_point = $for_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
        }

        $loop_scope = new LoopScope($for_context, $context);

        $loop_scope->protected_var_ids = array_merge(
            $assigned_var_ids,
            $context->protected_var_ids
        );

        LoopChecker::analyze(
            $statements_checker,
            $stmt->stmts,
            $stmt->cond,
            $stmt->loop,
            $loop_scope,
            $inner_loop_context
        );

        if (!$inner_loop_context) {
            throw new \UnexpectedValueException('There should be an inner loop context');
        }

        $always_enters_loop = false;

        foreach ($stmt->cond as $cond) {
            if (isset($cond->inferredType)) {
                foreach ($cond->inferredType->getTypes() as $iterator_type) {
                    $always_enters_loop = $iterator_type instanceof Type\Atomic\TTrue;

                    break;
                }
            }
        }

        if ($while_true) {
            $always_enters_loop = true;
        }

        $can_leave_loop = !$while_true
            || in_array(ScopeChecker::ACTION_BREAK, $loop_scope->final_actions, true);

        if ($always_enters_loop && $can_leave_loop) {
            foreach ($inner_loop_context->vars_in_scope as $var_id => $type) {
                // if there are break statements in the loop it's not certain
                // that the loop has finished executing
                if (in_array(ScopeChecker::ACTION_BREAK, $loop_scope->final_actions, true)
                    || in_array(ScopeChecker::ACTION_CONTINUE, $loop_scope->final_actions, true)
                ) {
                    if (isset($loop_scope->possibly_defined_loop_parent_vars[$var_id])) {
                        $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $type,
                            $loop_scope->possibly_defined_loop_parent_vars[$var_id]
                        );
                    }
                } else {
                    $context->vars_in_scope[$var_id] = $type;
                }
            }
        }

        if ($can_leave_loop) {
            $context->vars_possibly_in_scope = array_merge(
                $context->vars_possibly_in_scope,
                $for_context->vars_possibly_in_scope
            );
        } elseif ($pre_context) {
            $context->vars_possibly_in_scope = $pre_context->vars_possibly_in_scope;
        }

        $context->referenced_var_ids =
            $for_context->referenced_var_ids + $context->referenced_var_ids;

        if ($context->collect_references) {
            $context->unreferenced_vars = array_intersect_key(
                $for_context->unreferenced_vars,
                $context->unreferenced_vars
            );
        }

        if ($context->collect_references) {
            $context->unreferenced_vars = array_intersect_key(
                $for_context->unreferenced_vars,
                $context->unreferenced_vars
            );
        }

        if ($context->collect_exceptions) {
            $context->possibly_thrown_exceptions += $for_context->possibly_thrown_exceptions;
        }

        return null;
    }
}
