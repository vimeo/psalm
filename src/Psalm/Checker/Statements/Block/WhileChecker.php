<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\ScopeChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Context;
use Psalm\Scope\LoopScope;

class WhileChecker
{
    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Stmt\While_  $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\While_ $stmt,
        Context $context
    ) {
        $while_true = $stmt->cond
            && ($stmt->cond instanceof PhpParser\Node\Expr\ConstFetch && $stmt->cond->name->parts === ['true'])
                || ($stmt->cond instanceof PhpParser\Node\Scalar\LNumber && $stmt->cond->value > 0);

        $pre_context = null;

        if ($while_true) {
            $pre_context = clone $context;
        }

        $while_context = clone $context;

        $loop_scope = new LoopScope($while_context, $context);
        $loop_scope->protected_var_ids = $context->protected_var_ids;

        LoopChecker::analyze(
            $statements_checker,
            $stmt->stmts,
            $stmt->cond ? [$stmt->cond] : [],
            [],
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
                $while_context->vars_possibly_in_scope
            );
        } else {
            $context->vars_in_scope = $pre_context->vars_in_scope;
            $context->vars_possibly_in_scope = $pre_context->vars_possibly_in_scope;
        }

        $context->referenced_var_ids = array_merge(
            $context->referenced_var_ids,
            $while_context->referenced_var_ids
        );

        return null;
    }
}
