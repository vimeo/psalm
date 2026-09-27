<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\Pipe;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Expr\VirtualNullsafeMethodCall;
use Psalm\Node\Expr\VirtualStaticCall;
use Psalm\Node\VirtualPipeArg;
use Psalm\Type;

/**
 * @internal
 */
final class PipeAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        Pipe $stmt,
        Context $context,
    ): bool {
        $arg = new VirtualPipeArg(
            $stmt->left,
            false,
            false,
            $stmt->left->getAttributes(),
        );
        $args = [$arg];
        $right = $stmt->right;

        if ($right instanceof FuncCall && $right->isFirstClassCallable()) {
            $call = new VirtualFuncCall($right->name, $args, $right->getAttributes());
        } elseif ($right instanceof MethodCall && $right->isFirstClassCallable()) {
            $call = new VirtualMethodCall($right->var, $right->name, $args, $right->getAttributes());
        } elseif ($right instanceof NullsafeMethodCall && $right->isFirstClassCallable()) {
            $call = new VirtualNullsafeMethodCall($right->var, $right->name, $args, $right->getAttributes());
        } elseif ($right instanceof StaticCall && $right->isFirstClassCallable()) {
            $call = new VirtualStaticCall($right->class, $right->name, $args, $right->getAttributes());
        } else {
            $call = new VirtualFuncCall($right, $args, $right->getAttributes());
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $call, $context) === false) {
            return false;
        }

        $statements_analyzer->node_data->setType(
            $stmt,
            $statements_analyzer->node_data->getType($call) ?? Type::getMixed(),
        );

        return true;
    }
}
