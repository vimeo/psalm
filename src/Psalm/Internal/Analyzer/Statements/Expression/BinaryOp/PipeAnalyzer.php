<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArgPlaceholder;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\BinaryOp\Pipe;
use PhpParser\Node\Expr\CallLike;
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
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeFinder;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\Call\PartialApplicationAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Node\Expr\PartialApplication;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Expr\VirtualPipeValue;
use Psalm\Node\Expr\VirtualStaticCall;
use Psalm\Node\VirtualArg;
use Psalm\Type;

use function is_string;

/**
 * `$value |> $callable` calls `$callable($value)`. A first-class callable or a partial application with a single
 * placeholder is called directly, with `$value` in the place of the placeholder, as PHP does; any other partial
 * application creates its closure after `$value` is evaluated, and the closure is called with it.
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
        $direct_call = self::getDirectCall($right);

        // the closure of a direct call is never created, so only the syntax is checked
        if ($direct_call !== null && $right instanceof PartialApplication) {
            PartialApplicationAnalyzer::checkPhpVersion($statements_analyzer, $right);
        }

        if (self::isEvaluationOrderIrrelevant($left, $right, $direct_call, $context)) {
            $value = $left;
        } else {
            // PHP evaluates the left-hand side before any part of the right-hand side
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
        }

        $attributes = self::getCallAttributes($stmt, $direct_call[0] ?? null);
        $call = $direct_call !== null
            ? self::createDirectCall($direct_call[0], $direct_call[1], $value, $attributes)
            : new VirtualFuncCall(
                $right,
                [new VirtualArg($value, false, false, $value->getAttributes())],
                $attributes,
            );

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
     * The callee and the arguments as written, when PHP calls the callee of the right-hand side directly with the
     * piped value in the place of the placeholder: a first-class callable, or a partial application with a single
     * `?` or `...` and no named or unpacked argument before a `...`. A positional argument after a named one is a
     * compile error, reported by the partial application when it is analysed as one.
     *
     * @return array{FuncCall|MethodCall|StaticCall, list<Arg|ArgPlaceholder|VariadicPlaceholder>}|null
     */
    private static function getDirectCall(Expr $right): ?array
    {
        if ($right instanceof PartialApplication) {
            $callee = $right->call;
            $raw_args = $right->args;
        } elseif ($right instanceof CallLike && $right->isFirstClassCallable()) {
            $callee = $right;
            $raw_args = $right->getRawArgs();
        } else {
            return null;
        }

        if (!$callee instanceof FuncCall && !$callee instanceof MethodCall && !$callee instanceof StaticCall) {
            return null;
        }

        $placeholders = 0;
        $has_named = false;
        $has_named_or_unpacked = false;

        foreach ($raw_args as $arg) {
            if ($arg instanceof VariadicPlaceholder) {
                if ($has_named_or_unpacked) {
                    return null;
                }

                $placeholders++;
            } elseif ($arg->name !== null) {
                $has_named = $has_named_or_unpacked = true;

                if ($arg instanceof ArgPlaceholder) {
                    $placeholders++;
                }
            } elseif ($arg instanceof Arg && $arg->unpack) {
                $has_named_or_unpacked = true;
            } elseif ($has_named) {
                return null;
            } elseif ($arg instanceof ArgPlaceholder) {
                $placeholders++;
            }
        }

        return $placeholders === 1 ? [$callee, $raw_args] : null;
    }

    /**
     * The call spans the pipe but starts where no other analysed node does: calls are told apart by their start (issue
     * dedupe, data-flow specialization), and the pipe, like every pipe of a chain, starts with its left-hand side.
     *
     * @return array<string, mixed>
     */
    private static function getCallAttributes(Pipe $stmt, FuncCall|MethodCall|StaticCall|null $callee): array
    {
        $right = $stmt->right;
        $start = null;

        if (($callee instanceof MethodCall || $callee instanceof StaticCall) && $callee->name instanceof Identifier) {
            $start = $callee->name;
        } elseif ($callee instanceof FuncCall && $callee->name instanceof Name) {
            $start = $callee;
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
     * @param list<Arg|ArgPlaceholder|VariadicPlaceholder> $raw_args
     * @param array<string, mixed> $attributes
     */
    private static function createDirectCall(
        FuncCall|MethodCall|StaticCall $callee,
        array $raw_args,
        Expr $value,
        array $attributes,
    ): Expr {
        $has_placeholder = false;

        foreach ($raw_args as $raw_arg) {
            $has_placeholder = $has_placeholder || $raw_arg instanceof ArgPlaceholder;
        }

        $args = [];

        foreach ($raw_args as $raw_arg) {
            if ($raw_arg instanceof ArgPlaceholder) {
                $args[] = new VirtualArg($value, false, false, $raw_arg->getAttributes(), $raw_arg->name);
            } elseif ($raw_arg instanceof VariadicPlaceholder) {
                if (!$has_placeholder) {
                    $args[] = new VirtualArg($value, false, false, $raw_arg->getAttributes());
                }
            } else {
                $args[] = $raw_arg;
            }
        }

        if ($callee instanceof MethodCall) {
            return new VirtualMethodCall($callee->var, $callee->name, $args, $attributes);
        }

        if ($callee instanceof StaticCall) {
            return new VirtualStaticCall($callee->class, $callee->name, $args, $attributes);
        }

        return new VirtualFuncCall($callee->name, $args, $attributes);
    }

    /**
     * Whether analysing the right-hand side before the left-hand side, as a call does, gives the same result as
     * PHP's left-to-right evaluation.
     *
     * @param array{FuncCall|MethodCall|StaticCall, list<Arg|ArgPlaceholder|VariadicPlaceholder>}|null $direct_call
     */
    private static function isEvaluationOrderIrrelevant(
        Expr $left,
        Expr $right,
        ?array $direct_call,
        Context $context,
    ): bool {
        if (self::capturesNothing($left)) {
            return true;
        }

        if (!self::isWriteFree($left, $context)) {
            return false;
        }

        if ($direct_call === null) {
            return self::isWriteFree($right, $context);
        }

        [$callee, $raw_args] = $direct_call;

        if (($callee instanceof FuncCall
                && !$callee->name instanceof Name
                && !self::isWriteFree($callee->name, $context))
            || ($callee instanceof MethodCall
                && (!$callee->name instanceof Identifier || !self::isWriteFree($callee->var, $context)))
            || ($callee instanceof StaticCall
                && (!$callee->class instanceof Name || !$callee->name instanceof Identifier))
        ) {
            return false;
        }

        foreach ($raw_args as $raw_arg) {
            if ($raw_arg instanceof Arg && !self::isWriteFree($raw_arg->value, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the expression is a callable literal that reads no variable the other side of the pipe could write: a
     * closure without `use`, or an arrow function reading only its parameters and `$this`.
     */
    private static function capturesNothing(Expr $expr): bool
    {
        if ($expr instanceof Closure) {
            return $expr->uses === [];
        }

        if (!$expr instanceof ArrowFunction) {
            return false;
        }

        $own_names = ['this' => true];

        foreach ($expr->params as $param) {
            if ($param->var instanceof Variable && is_string($param->var->name)) {
                $own_names[$param->var->name] = true;
            }
        }

        return (new NodeFinder())->findFirst(
            $expr->expr,
            static fn(Node $node): bool => $node instanceof Variable
                && (!is_string($node->name) || !isset($own_names[$node->name])),
        ) === null;
    }

    /**
     * Whether evaluating the expression cannot change any state the other side of the pipe may read.
     */
    private static function isWriteFree(Expr $expr, Context $context): bool
    {
        if ($expr instanceof Closure) {
            foreach ($expr->uses as $use) {
                if ($use->byRef) {
                    return false;
                }
            }

            return true;
        }

        if ($expr instanceof Array_) {
            foreach ($expr->items as $item) {
                if ($item->byRef
                    || $item->unpack
                    || ($item->key !== null && !self::isWriteFree($item->key, $context))
                    || !self::isWriteFree($item->value, $context)
                ) {
                    return false;
                }
            }

            return true;
        }

        if ($expr instanceof ArrayDimFetch) {
            if (!$expr->var instanceof Variable
                || !is_string($expr->var->name)
                || $expr->dim === null
                || !self::isWriteFree($expr->dim, $context)
            ) {
                return false;
            }

            $var_id = '$' . $expr->var->name;

            // an offset of an `ArrayAccess` object is read by calling `offsetGet()`
            return isset($context->vars_in_scope[$var_id])
                ? $context->vars_in_scope[$var_id]->isArray()
                : VariableFetchAnalyzer::isSuperGlobal($var_id);
        }

        if ($expr instanceof MethodCall && $expr->isFirstClassCallable()) {
            return $expr->name instanceof Identifier && self::isWriteFree($expr->var, $context);
        }

        return ($expr instanceof Variable && is_string($expr->name))
            || ($expr instanceof Scalar && !$expr instanceof InterpolatedString)
            || $expr instanceof ConstFetch
            || ($expr instanceof ClassConstFetch && $expr->class instanceof Name && $expr->name instanceof Identifier)
            || $expr instanceof ArrowFunction
            || ($expr instanceof FuncCall && $expr->isFirstClassCallable() && $expr->name instanceof Name)
            || ($expr instanceof StaticCall && $expr->isFirstClassCallable() && $expr->class instanceof Name
                && $expr->name instanceof Identifier);
    }
}
