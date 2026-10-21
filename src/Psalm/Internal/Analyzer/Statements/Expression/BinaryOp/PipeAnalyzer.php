<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\BinaryOp\Pipe;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Scalar\InterpolatedString;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Expr\VirtualPipeValue;
use Psalm\Node\Expr\VirtualStaticCall;
use Psalm\Node\VirtualPipeArg;
use Psalm\Type;

use function is_string;

/**
 * @internal
 */
final class PipeAnalyzer
{
    /**
     * Attribute of a Pipe node holding the call it was analysed as, so assertions can be derived from it.
     */
    public const CALL_ATTRIBUTE = 'pipeCall';

    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        Pipe $stmt,
        Context $context,
    ): bool {
        $left = $stmt->left;

        if (self::isEvaluationOrderIrrelevant($left, $stmt->right)) {
            // The call analyser sees the real expression: closures get typed from the parameter
            // and assertions can narrow the piped variable.
            $arg_value = $left;
        } else {
            // PHP evaluates the left-hand side before any part of the right-hand side.
            $was_inside_call = $context->inside_call;
            $context->inside_call = true;
            $left_result = ExpressionAnalyzer::analyze($statements_analyzer, $left, $context);
            $context->inside_call = $was_inside_call;

            if ($left_result === false) {
                return false;
            }

            $arg_value = new VirtualPipeValue(
                $statements_analyzer->node_data->getType($left) ?? Type::getMixed(),
                $left->getAttributes(),
            );
        }

        $call = self::createCall(
            $stmt->right,
            new VirtualPipeArg($arg_value, false, false, $left->getAttributes()),
        );
        $stmt->setAttribute(self::CALL_ATTRIBUTE, $call);

        if (ExpressionAnalyzer::analyze($statements_analyzer, $call, $context) === false) {
            return false;
        }

        $statements_analyzer->node_data->setType(
            $stmt,
            $statements_analyzer->node_data->getType($call) ?? Type::getMixed(),
        );

        if ($call->getAttribute('pure', false)) {
            $stmt->setAttribute('pure', true);
        }

        return true;
    }

    private static function createCall(Expr $right, VirtualPipeArg $arg): Expr
    {
        if ($right instanceof FuncCall && $right->isFirstClassCallable()) {
            return new VirtualFuncCall($right->name, [$arg], $right->getAttributes());
        }

        if ($right instanceof MethodCall && $right->isFirstClassCallable()) {
            return new VirtualMethodCall($right->var, $right->name, [$arg], $right->getAttributes());
        }

        if ($right instanceof StaticCall && $right->isFirstClassCallable()) {
            return new VirtualStaticCall($right->class, $right->name, [$arg], $right->getAttributes());
        }

        return new VirtualFuncCall($right, [$arg], $right->getAttributes());
    }

    /**
     * Whether analysing the right-hand side callee before the left-hand side (as a call does)
     * gives the same result as PHP's left-to-right evaluation.
     */
    private static function isEvaluationOrderIrrelevant(Expr $left, Expr $right): bool
    {
        // the callee is resolved without reading any variable
        if (($right instanceof FuncCall && $right->isFirstClassCallable() && $right->name instanceof Name)
            || ($right instanceof StaticCall
                && $right->isFirstClassCallable()
                && $right->class instanceof Name
                && $right->name instanceof Identifier)
        ) {
            return true;
        }

        if (!self::isWriteFree($left)) {
            return false;
        }

        if ($right instanceof MethodCall && $right->isFirstClassCallable()) {
            return $right->name instanceof Identifier && self::isWriteFree($right->var);
        }

        return self::isWriteFree($right);
    }

    /**
     * Whether evaluating the expression cannot change any state the other side of the pipe may read.
     */
    private static function isWriteFree(Expr $expr): bool
    {
        if ($expr instanceof Closure) {
            foreach ($expr->uses as $use) {
                if ($use->byRef) {
                    return false;
                }
            }

            return true;
        }

        return ($expr instanceof Variable && is_string($expr->name))
            || ($expr instanceof Scalar && !$expr instanceof InterpolatedString)
            || $expr instanceof ConstFetch
            || ($expr instanceof ClassConstFetch && $expr->class instanceof Name && $expr->name instanceof Identifier)
            || $expr instanceof ArrowFunction;
    }
}
