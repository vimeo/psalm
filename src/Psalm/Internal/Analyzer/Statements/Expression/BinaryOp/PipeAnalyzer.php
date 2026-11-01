<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser\Node\Arg;
use PhpParser\Node\ArgPlaceholder;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Pipe;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\VariadicPlaceholder;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Expr\VirtualPipeValue;
use Psalm\Node\Expr\VirtualStaticCall;
use Psalm\Node\VirtualPipeArg;
use Psalm\Type;

use function assert;

/**
 * `$value |> $callable` calls `$callable($value)`. A first-class callable or a partial application with a single
 * placeholder is called directly, with `$value` in the place of the placeholder, as PHP does.
 *
 * @internal
 */
final class PipeAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        Pipe $stmt,
        Context $context,
    ): bool {
        $left = $stmt->left;
        $right = $stmt->right;

        if ($right instanceof CallLike && $right->isPartialFunctionApplication() && !self::isDirectCall($right)) {
            return self::analyzeOperands($statements_analyzer, $stmt, $right, $context);
        }

        // PHP evaluates the left-hand side before any part of the right-hand side, which a call analyses first
        $was_inside_call = $context->inside_call;
        $was_inside_isset = $context->inside_isset;
        $context->inside_call = true;
        $context->inside_isset = false;
        $left_result = ExpressionAnalyzer::analyze($statements_analyzer, $left, $context);
        $context->inside_call = $was_inside_call;
        $context->inside_isset = $was_inside_isset;

        if ($left_result === false) {
            return false;
        }

        $value = new VirtualPipeValue(
            $statements_analyzer->node_data->getType($left) ?? Type::getMixed(),
            $left->getAttributes(),
        );

        $call = self::createCall($right, $value, self::getCallAttributes($stmt));

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

    /**
     * Whether the closure of the partial application is called like the callee with the piped value in the place of
     * the placeholder: with one `?`, or with a `...` that only positional arguments precede.
     *
     * @psalm-capabilities read-props
     */
    private static function isDirectCall(CallLike $call): bool
    {
        if (!$call instanceof FuncCall && !$call instanceof MethodCall && !$call instanceof StaticCall) {
            return false;
        }

        $placeholders = 0;
        $named_before_variadic_placeholder = false;
        $seen_variadic_placeholder = false;

        foreach ($call->getRawArgs() as $arg) {
            if ($arg instanceof ArgPlaceholder) {
                $placeholders++;
            } elseif ($arg instanceof VariadicPlaceholder) {
                $seen_variadic_placeholder = true;
            } elseif (!$seen_variadic_placeholder && ($arg->name !== null || $arg->unpack)) {
                $named_before_variadic_placeholder = true;
            }
        }

        return $placeholders === 1 || ($placeholders === 0 && !$named_before_variadic_placeholder);
    }

    /**
     * The call spans the pipe but starts where no other analysed node does: calls are told apart by their start (issue
     * dedupe, data-flow specialization), and the pipe, like every pipe of a chain, starts with its left-hand side.
     *
     * @return array<string, mixed>
     */
    private static function getCallAttributes(Pipe $stmt): array
    {
        $right = $stmt->right;
        $start = null;

        if ($right instanceof CallLike && $right->isPartialFunctionApplication()) {
            if (($right instanceof MethodCall || $right instanceof StaticCall) && $right->name instanceof Identifier) {
                $start = $right->name;
            } elseif ($right instanceof FuncCall && $right->name instanceof Name) {
                $start = $right;
            }
        }

        $attributes = $stmt->getAttributes();

        if ($start !== null) {
            $attributes['startLine'] = $start->getStartLine();
            $attributes['startFilePos'] = $start->getStartFilePos();
            $attributes['startTokenPos'] = $start->getStartTokenPos();
        } else {
            // between the operands, at the end of the `|>` or of a parenthesis around the right-hand side
            $attributes['startLine'] = $right->getStartLine();
            $attributes['startFilePos'] = $right->getStartFilePos() - 1;
            $attributes['startTokenPos'] = $right->getStartTokenPos() - 1;
        }

        return $attributes;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private static function createCall(Expr $right, Expr $value, array $attributes): Expr
    {
        if (!$right instanceof CallLike || !$right->isPartialFunctionApplication()) {
            return new VirtualFuncCall(
                $right,
                [new VirtualPipeArg($value, false, false, $value->getAttributes())],
                $attributes,
            );
        }

        $has_placeholder = false;

        foreach ($right->getRawArgs() as $raw_arg) {
            $has_placeholder = $has_placeholder || $raw_arg instanceof ArgPlaceholder;
        }

        $args = [];

        foreach ($right->getRawArgs() as $raw_arg) {
            if ($raw_arg instanceof ArgPlaceholder) {
                $args[] = new VirtualPipeArg($value, false, false, $raw_arg->getAttributes(), $raw_arg->name);
            } elseif ($raw_arg instanceof VariadicPlaceholder) {
                if (!$has_placeholder) {
                    $args[] = new VirtualPipeArg($value, false, false, $raw_arg->getAttributes());
                }
            } else {
                $args[] = $raw_arg;
            }
        }

        if ($right instanceof MethodCall) {
            return new VirtualMethodCall($right->var, $right->name, $args, $attributes);
        }

        if ($right instanceof StaticCall) {
            return new VirtualStaticCall($right->class, $right->name, $args, $attributes);
        }

        assert($right instanceof FuncCall);

        return new VirtualFuncCall($right->name, $args, $attributes);
    }

    /**
     * A partial application whose closure the piped value is not the only argument of, or that PHP does not compile:
     * its operands are analysed, the result is unknown.
     */
    private static function analyzeOperands(
        StatementsAnalyzer $statements_analyzer,
        Pipe $stmt,
        CallLike $right,
        Context $context,
    ): bool {
        $operands = [$stmt->left];

        if ($right instanceof FuncCall && $right->name instanceof Expr) {
            $operands[] = $right->name;
        } elseif ($right instanceof MethodCall || $right instanceof NullsafeMethodCall) {
            $operands[] = $right->var;
        } elseif (($right instanceof StaticCall || $right instanceof New_) && $right->class instanceof Expr) {
            $operands[] = $right->class;
        }

        if (($right instanceof MethodCall || $right instanceof NullsafeMethodCall || $right instanceof StaticCall)
            && $right->name instanceof Expr
        ) {
            $operands[] = $right->name;
        }

        foreach ($right->getRawArgs() as $raw_arg) {
            if ($raw_arg instanceof Arg) {
                $operands[] = $raw_arg->value;
            }
        }

        $was_inside_call = $context->inside_call;
        $was_inside_isset = $context->inside_isset;
        $context->inside_call = true;
        $context->inside_isset = false;

        foreach ($operands as $operand) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $operand, $context) === false) {
                $context->inside_call = $was_inside_call;
                $context->inside_isset = $was_inside_isset;

                return false;
            }
        }

        $context->inside_call = $was_inside_call;
        $context->inside_isset = $was_inside_isset;

        $statements_analyzer->node_data->setType($stmt, Type::getMixed());

        return true;
    }
}
