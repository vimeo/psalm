<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;

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

        return LoopAnalyzer::analyzeForOrWhile(
            $statements_analyzer,
            $stmt,
            $context,
            $while_true,
            [],
            [],
            self::getAndExpressions($stmt->cond),
            [],
        );
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
