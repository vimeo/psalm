<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\ScopeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Context;
use Psalm\Scope\LoopScope;

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

        $pre_context = $while_true ? clone $context : null;

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

        if ($inner_loop_context && $while_true) {
            // if we actually leave the loop
            if (in_array(ScopeChecker::ACTION_BREAK, $loop_scope->final_actions, true)
                || in_array(ScopeChecker::ACTION_END, $loop_scope->final_actions, true)
            ) {
                foreach ($inner_loop_context->vars_in_scope as $var_id => $type) {
                    if (!isset($context->vars_in_scope[$var_id])) {
                        $context->vars_in_scope[$var_id] = $type;
                    }
                }
            }
        }

        if (!$while_true
            || in_array(ScopeChecker::ACTION_BREAK, $loop_scope->final_actions, true)
            || in_array(ScopeChecker::ACTION_END, $loop_scope->final_actions, true)
            || !$pre_context
        ) {
            $context->vars_possibly_in_scope = array_merge(
                $context->vars_possibly_in_scope,
                $for_context->vars_possibly_in_scope
            );

            $context->possibly_assigned_var_ids =
                $for_context->possibly_assigned_var_ids + $context->possibly_assigned_var_ids;
        } else {
            $context->vars_in_scope = $pre_context->vars_in_scope;
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

        return null;
    }
}
