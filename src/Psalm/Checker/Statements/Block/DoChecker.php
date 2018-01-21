<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Context;
use Psalm\Scope\LoopScope;
use Psalm\Type;

class DoChecker
{
    /**
     * @return false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\Do_ $stmt,
        Context $context
    ) {
        $do_context = clone $context;

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        if ($project_checker->alter_code) {
            $do_context->branch_point = $do_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
        }

        $loop_scope = new LoopScope($do_context, $context);
        $loop_scope->protected_var_ids = $context->protected_var_ids;

        LoopChecker::analyze($statements_checker, $stmt->stmts, [], [], $loop_scope, $inner_loop_context);

        foreach ($context->vars_in_scope as $var => $type) {
            if ($type->isMixed()) {
                continue;
            }

            if ($do_context->hasVariable($var)) {
                if ($do_context->vars_in_scope[$var]->isMixed()) {
                    $context->vars_in_scope[$var] = $do_context->vars_in_scope[$var];
                }

                if ($do_context->vars_in_scope[$var]->getId() !== $type->getId()) {
                    $context->vars_in_scope[$var] = Type::combineUnionTypes($do_context->vars_in_scope[$var], $type);
                }
            }
        }

        foreach ($do_context->vars_in_scope as $var_id => $type) {
            if (!isset($context->vars_in_scope[$var_id])) {
                $context->vars_in_scope[$var_id] = $type;
            }
        }

        // because it's a do {} while, inner loop vars belong to the main context
        if ($inner_loop_context) {
            foreach ($inner_loop_context->vars_in_scope as $var_id => $type) {
                if (!isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = $type;
                }
            }
        }

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $do_context->vars_possibly_in_scope
        );

        $context->referenced_var_ids = array_merge(
            $context->referenced_var_ids,
            $do_context->referenced_var_ids
        );

        return ExpressionChecker::analyze($statements_checker, $stmt->cond, $context);
    }
}
