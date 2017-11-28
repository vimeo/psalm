<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\StatementsChecker;
use Psalm\Context;

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
        $while_context = clone $context;

        $statements_checker->analyzeLoop(
            $stmt->stmts,
            $stmt->cond ? [$stmt->cond] : [],
            [],
            $while_context,
            $context
        );

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $while_context->vars_possibly_in_scope
        );

        $context->referenced_var_ids = array_merge(
            $context->referenced_var_ids,
            $while_context->referenced_var_ids
        );

        return null;
    }
}
