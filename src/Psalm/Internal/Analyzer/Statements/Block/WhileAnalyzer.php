<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;

use function is_string;

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

        // the variable a condition like `++$i < 10` starts from, if it is a literal before the loop
        $init_var_types = [];
        if ($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp) {
            $cond_variable = $stmt->cond->left;
            if ($cond_variable instanceof PhpParser\Node\Expr\PreInc
                || $cond_variable instanceof PhpParser\Node\Expr\PreDec
            ) {
                $cond_variable = $cond_variable->var;
            }

            if ($cond_variable instanceof PhpParser\Node\Expr\Variable
                && is_string($cond_variable->name)
                && isset($context->vars_in_scope['$' . $cond_variable->name])
                && !$context->vars_in_scope['$' . $cond_variable->name]->possibly_undefined
            ) {
                $init_var_types[$cond_variable->name] = $context->vars_in_scope['$' . $cond_variable->name];
            }
        }

        return LoopAnalyzer::analyzeForOrWhile(
            $statements_analyzer,
            $stmt,
            $context,
            $while_true,
            $init_var_types,
            [],
            self::getAndExpressions($stmt->cond),
            [],
        );
    }

    /**
     * @return list<PhpParser\Node\Expr>
     * @psalm-mutation-free
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
