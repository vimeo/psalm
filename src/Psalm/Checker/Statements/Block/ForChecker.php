<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
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
        foreach ($stmt->init as $init) {
            if (ExpressionChecker::analyze($statements_checker, $init, $context) === false) {
                return false;
            }
        }

        $for_context = clone $context;
        $for_context->inside_loop = true;

        LoopChecker::analyze(
            $statements_checker,
            $stmt->stmts,
            $stmt->cond,
            $stmt->loop,
            new LoopScope($for_context, $context)
        );

        $context->vars_possibly_in_scope = array_merge(
            $for_context->vars_possibly_in_scope,
            $context->vars_possibly_in_scope
        );

        $context->referenced_var_ids = array_merge(
            $for_context->referenced_var_ids,
            $context->referenced_var_ids
        );

        return null;
    }
}
