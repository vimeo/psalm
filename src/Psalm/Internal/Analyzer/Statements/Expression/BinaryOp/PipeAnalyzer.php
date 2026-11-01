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
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\VarLikeIdentifier;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeAbstract;
use PhpParser\NodeFinder;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Expr\VirtualPipeValue;
use Psalm\Node\Expr\VirtualStaticCall;
use Psalm\Node\VirtualArg;
use Psalm\Type;

use function assert;
use function is_string;

/**
 * `$value |> $callable` calls `$callable($value)`. A first-class callable or a partial application with a single
 * placeholder is called directly, with `$value` in the place of the placeholder, as PHP does.
 *
 * @internal
 */
final class PipeAnalyzer
{
    /**
     * The call a pipe made when it was last analysed
     */
    private const CALL_ATTRIBUTE = 'pipeCall';

    /**
     * The pipe that makes a call
     */
    private const PIPE_ATTRIBUTE = 'pipe';

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

        if (self::isEvaluationOrderIrrelevant($left, $right, $context)) {
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

        $call = self::createCall($right, $value, self::getCallAttributes($stmt));
        $call->setAttribute(self::PIPE_ATTRIBUTE, $stmt);
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

    /**
     * The call the pipe makes: the one it made when it was last analysed or, before then, the call that reads the
     * left-hand side in place. None where the pipe makes no call, or passes a value it reads first, which asserts
     * nothing about a variable.
     */
    public static function getCall(Pipe $stmt): ?Expr
    {
        /** @var Expr|null $call */
        $call = $stmt->getAttribute(self::CALL_ATTRIBUTE);

        if ($call !== null) {
            return $call;
        }

        $right = $stmt->right;

        if (($right instanceof CallLike && $right->isPartialFunctionApplication() && !self::isDirectCall($right))
            || !self::isEvaluationOrderIrrelevant($stmt->left, $right, null)
        ) {
            return null;
        }

        return self::createCall($right, $stmt->left, self::getCallAttributes($stmt));
    }

    /**
     * Where what a function call asserts about its arguments is reported, the call or its name otherwise. The call of a
     * pipe is reported at the pipe, which stands where the hand-written call (and its name) would: a condition the
     * pipe is reports the same issue there, once.
     *
     * @template TNode of NodeAbstract
     * @param TNode $node
     * @return TNode|Pipe
     */
    public static function getAssertionLocationNode(FuncCall $call, NodeAbstract $node): NodeAbstract
    {
        /** @var Pipe|null $pipe */
        $pipe = $call->getAttribute(self::PIPE_ATTRIBUTE);

        return $pipe ?? $node;
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
                [new VirtualArg($value, false, false, $value->getAttributes())],
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
                $args[] = new VirtualArg($value, false, false, $raw_arg->getAttributes(), $raw_arg->name);
            } elseif ($raw_arg instanceof VariadicPlaceholder) {
                if (!$has_placeholder) {
                    $args[] = new VirtualArg($value, false, false, $raw_arg->getAttributes());
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

    /**
     * Whether analysing the right-hand side before the left-hand side, as a call does, gives the same result as
     * PHP's left-to-right evaluation. Without a context, before the pipe is analysed, only where no type decides.
     */
    private static function isEvaluationOrderIrrelevant(Expr $left, Expr $right, ?Context $context): bool
    {
        if (self::capturesNothing($left)) {
            return true;
        }

        if (!self::isWriteFree($left, $context)) {
            // reading a property or an offset may run code (`__get()`, a hook, `offsetGet()`), which cannot change
            // what a right-hand side reading nothing changeable evaluates to
            return self::isFetch($left) && self::readsNothingChangeable($right);
        }

        if (!$right instanceof CallLike || !$right->isPartialFunctionApplication()) {
            return self::isWriteFree($right, $context);
        }

        if (($right instanceof FuncCall && !$right->name instanceof Name && !self::isWriteFree($right->name, $context))
            || ($right instanceof MethodCall
                && (!$right->name instanceof Identifier || !self::isWriteFree($right->var, $context)))
            || ($right instanceof StaticCall
                && (!$right->class instanceof Name || !$right->name instanceof Identifier))
        ) {
            return false;
        }

        foreach ($right->getRawArgs() as $raw_arg) {
            if ($raw_arg instanceof Arg && !self::isWriteFree($raw_arg->value, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the expression reads a property, a static property or an offset of a variable.
     */
    private static function isFetch(Expr $expr): bool
    {
        return ($expr instanceof PropertyFetch && $expr->var instanceof Variable && $expr->name instanceof Identifier)
            || ($expr instanceof StaticPropertyFetch && $expr->class instanceof Name
                && $expr->name instanceof VarLikeIdentifier)
            || ($expr instanceof ArrayDimFetch && $expr->var instanceof Variable && $expr->dim !== null
                && self::isWriteFree($expr->dim, null));
    }

    /**
     * Whether the right-hand side names its callee, a function, a static method or a method of `$this`, and binds
     * only constants.
     */
    private static function readsNothingChangeable(Expr $right): bool
    {
        if ((!$right instanceof FuncCall || !$right->name instanceof Name)
            && (!$right instanceof StaticCall || !$right->class instanceof Name
                || !$right->name instanceof Identifier)
            && (!$right instanceof MethodCall || !$right->var instanceof Variable || $right->var->name !== 'this'
                || !$right->name instanceof Identifier)
        ) {
            return false;
        }

        if (!$right->isPartialFunctionApplication()) {
            return false;
        }

        foreach ($right->getRawArgs() as $raw_arg) {
            if ($raw_arg instanceof Arg
                && !($raw_arg->value instanceof Scalar && !$raw_arg->value instanceof InterpolatedString)
                && !$raw_arg->value instanceof ConstFetch
                && !($raw_arg->value instanceof ClassConstFetch && $raw_arg->value->class instanceof Name
                    && $raw_arg->value->name instanceof Identifier)
            ) {
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
    private static function isWriteFree(Expr $expr, ?Context $context): bool
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
            return $context !== null && isset($context->vars_in_scope[$var_id])
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
