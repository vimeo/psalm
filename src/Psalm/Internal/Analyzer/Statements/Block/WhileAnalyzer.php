<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Scope\LoopScope;
use UnexpectedValueException;

use function in_array;

/**
 * @internal
 */
final class WhileAnalyzer
{
    /**
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\While_ $stmt,
        Context $context,
    ): ?bool {
        $while_true = ($stmt->cond instanceof PhpParser\Node\Expr\ConstFetch
                && $stmt->cond->name->getParts() === ['true'])
            || (($t = $statements_analyzer->node_data->getType($stmt->cond))
                && $t->isAlwaysTruthy());

        $pre_context = null;

        if ($while_true) {
            $pre_context = clone $context;
        }

        $while_context = clone $context;

        $while_context->inside_loop = true;
        $while_context->break_types[] = 'loop';

        $codebase = $statements_analyzer->getCodebase();

        if ($codebase->alter_code) {
            $while_context->branch_point = $while_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
        }

        $loop_scope = new LoopScope($while_context, $context);
        $loop_scope->protected_var_ids = $context->protected_var_ids;

        if (LoopAnalyzer::analyze(
            $statements_analyzer,
            $stmt->stmts,
            self::getAndExpressions($stmt->cond),
            [],
            $loop_scope,
            $inner_loop_context,
        ) === false) {
            return false;
        }

        if (!$inner_loop_context) {
            throw new UnexpectedValueException('There should be an inner loop context');
        }

        $always_enters_loop = false;

        if ($stmt_cond_type = $statements_analyzer->node_data->getType($stmt->cond)) {
            $always_enters_loop = $stmt_cond_type->isAlwaysTruthy();
        }

        if ($while_true) {
            $always_enters_loop = true;
        }

        $can_leave_loop = !$while_true
            || in_array(ScopeAnalyzer::ACTION_BREAK, $loop_scope->final_actions, true);

        if ($always_enters_loop && $can_leave_loop) {
            LoopAnalyzer::setLoopVars($inner_loop_context, $context, $loop_scope);
        }

        $while_context->loop_scope = null;

        if ($can_leave_loop) {
            $context->vars_possibly_in_scope = [
                ...$context->vars_possibly_in_scope,
                ...$while_context->vars_possibly_in_scope,
            ];
        } elseif ($pre_context) {
            $context->vars_possibly_in_scope = $pre_context->vars_possibly_in_scope;
        }

        if ($context->collect_exceptions) {
            $context->mergeExceptions($while_context);
        }

        return null;
    }

    /**
     * @return list<PhpParser\Node\Expr>
     */
    public static function getAndExpressions(
        PhpParser\Node\Expr $expr,
    ): array {
        if ($expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
            return [...self::getAndExpressions($expr->left), ...self::getAndExpressions($expr->right)];
        }

        return [$expr];
    }
}
