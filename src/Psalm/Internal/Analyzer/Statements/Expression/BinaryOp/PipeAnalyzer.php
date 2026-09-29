<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\Pipe;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Expr\VirtualStaticCall;
use Psalm\Node\Expr\VirtualVariable;
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
        // PHP evaluates the left-hand side before any part of the right-hand side,
        // so analyse it first and hand its result to the call through a temporary variable.
        $was_inside_call = $context->inside_call;
        $context->inside_call = true;
        $left_result = ExpressionAnalyzer::analyze($statements_analyzer, $stmt->left, $context);
        $context->inside_call = $was_inside_call;

        if ($left_result === false) {
            return false;
        }

        $tmp_name = '__tmp_pipe__' . (int) $stmt->left->getAttribute('startFilePos');
        $context->vars_in_scope['$' . $tmp_name] = $statements_analyzer->node_data->getType($stmt->left)
            ?? Type::getMixed();

        $args = [
            new VirtualPipeArg(
                new VirtualVariable($tmp_name, $stmt->left->getAttributes()),
                false,
                false,
                $stmt->left->getAttributes(),
            ),
        ];
        $right = $stmt->right;

        if ($right instanceof FuncCall && $right->isFirstClassCallable()) {
            $call = new VirtualFuncCall($right->name, $args, $right->getAttributes());
        } elseif ($right instanceof MethodCall && $right->isFirstClassCallable()) {
            $call = new VirtualMethodCall($right->var, $right->name, $args, $right->getAttributes());
        } elseif ($right instanceof StaticCall && $right->isFirstClassCallable()) {
            $call = new VirtualStaticCall($right->class, $right->name, $args, $right->getAttributes());
        } else {
            $call = new VirtualFuncCall($right, $args, $right->getAttributes());
        }

        $call_result = ExpressionAnalyzer::analyze($statements_analyzer, $call, $context);

        unset($context->vars_in_scope['$' . $tmp_name]);

        if ($call_result === false) {
            return false;
        }

        $statements_analyzer->node_data->setType(
            $stmt,
            $statements_analyzer->node_data->getType($call) ?? Type::getMixed(),
        );

        return true;
    }
}
