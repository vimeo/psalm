<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use Override;
use PhpParser\Error;
use PhpParser\ErrorHandler;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArgPlaceholder;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\Pipe;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeVisitorAbstract;
use Psalm\Node\Expr\PartialApplication;
use WeakMap;

use function array_filter;
use function array_values;
use function count;
use function strtolower;

/**
 * Replaces the call node of each partial function application with a PartialApplication node, and removes the
 * placeholders of attribute arguments, so that no argument list Psalm analyzes holds a placeholder other than a
 * first-class callable's `...`. Reports the compile errors PHP 8.6 raises for them as parse errors.
 *
 * @internal
 */
final class PartialApplicationVisitor extends NodeVisitorAbstract
{
    /**
     * The compile error of a PartialApplication node, which analysis types as a plain Closure, or of an Attribute node
     * whose placeholders were removed, which analysis does not construct
     */
    public const COMPILE_ERROR = 'psalmCompileError';

    /** @var WeakMap<Expr, true> the right-hand sides of the pipes being traversed */
    private WeakMap $pipe_callables;

    /**
     * @psalm-capabilities read-props
     */
    public function __construct(
        private readonly ErrorHandler $error_handler,
    ) {
        /** @var WeakMap<Expr, true> */
        $this->pipe_callables = new WeakMap();
    }

    #[Override]
    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof Pipe) {
            $this->pipe_callables[$node->right] = true;
        }

        return null;
    }

    #[Override]
    public function leaveNode(Node $node): ?Node
    {
        if ($node instanceof Attribute) {
            $args = array_values(array_filter($node->args, static fn(Node $arg): bool => $arg instanceof Arg));

            if (count($args) !== count($node->args)) {
                $node->args = $args;
                $node->setAttribute(self::COMPILE_ERROR, 'Cannot create Closure as attribute argument');
            }

            // also reports the attributes a partial reparse kept (see PartialParserVisitor)
            $this->reportCompileError($node);

            return null;
        }

        if ($node instanceof PartialApplication) {
            // kept by a partial reparse (see PartialParserVisitor)
            $this->reportCompileError($node);

            return null;
        }

        if (!($node instanceof FuncCall
                || $node instanceof MethodCall
                || $node instanceof NullsafeMethodCall
                || $node instanceof StaticCall
                || $node instanceof New_)
            || !$node->isPartialFunctionApplication()
            || $node->isFirstClassCallable()
        ) {
            return null;
        }

        $call = clone $node;
        $call->args = [new VariadicPlaceholder($node->getAttributes())];

        $partial_application = new PartialApplication($call, $node->getRawArgs(), $node->getAttributes());

        $compile_error = self::getCompileError($node, isset($this->pipe_callables[$node]));

        if ($compile_error !== null) {
            $partial_application->setAttribute(self::COMPILE_ERROR, $compile_error);
            $this->reportCompileError($partial_application);
        }

        return $partial_application;
    }

    /**
     * Whether PHP calls the callee of `$value |> callee(...)` directly, with `$value` in the place of the only
     * placeholder, instead of creating a closure (zend_partial_apply())
     *
     * @param array<Arg|ArgPlaceholder|VariadicPlaceholder> $raw_args
     * @psalm-assert-if-true FuncCall|MethodCall|StaticCall $callee
     * @psalm-capabilities read-props
     */
    public static function isDirectPipeCall(Expr $callee, array $raw_args): bool
    {
        if (!$callee instanceof FuncCall && !$callee instanceof MethodCall && !$callee instanceof StaticCall) {
            return false;
        }

        // never assert(), by the name as written whatever it resolves to (zend_is_pipe_optimizable_callable_name())
        if ($callee instanceof FuncCall
            && $callee->name instanceof Name
            && strtolower($callee->name->name) === 'assert'
        ) {
            return false;
        }

        $placeholders = 0;
        $has_named = false;

        foreach ($raw_args as $arg) {
            if ($arg instanceof VariadicPlaceholder) {
                if ($has_named) {
                    return false;
                }

                $placeholders++;
            } else {
                $has_named = $has_named || $arg->name !== null;

                if ($arg instanceof ArgPlaceholder) {
                    $placeholders++;
                }
            }
        }

        return $placeholders === 1;
    }

    private function reportCompileError(Attribute|PartialApplication $node): void
    {
        /** @var string|null $compile_error set by leaveNode() */
        $compile_error = $node->getAttribute(self::COMPILE_ERROR);

        if ($compile_error !== null) {
            $this->error_handler->handleError(new Error($compile_error, $node->getAttributes()));
        }
    }

    /**
     * The first error PHP raises when it compiles the partial application, in its order of checks
     *
     * @psalm-capabilities read-props
     */
    private static function getCompileError(
        FuncCall|MethodCall|NullsafeMethodCall|StaticCall|New_ $call,
        bool $is_pipe_callable,
    ): ?string {
        if ($call instanceof New_) {
            return 'Cannot create Closure for new expression';
        }

        $raw_args = $call->args;

        // a direct call is compiled as a call with the piped value in the place of the placeholder
        if ($is_pipe_callable && self::isDirectPipeCall($call, $raw_args)) {
            return self::getArgumentsError($raw_args, false);
        }

        $arguments_error = self::getArgumentsError($raw_args, true);

        if ($arguments_error !== null) {
            return $arguments_error;
        }

        // PHP checks the object chain of a method call only: the class of a static call may be a nullsafe expression
        if ($call instanceof NullsafeMethodCall
            || ($call instanceof MethodCall && self::isShortCircuited($call->var))
        ) {
            return 'Cannot combine nullsafe operator with Closure creation';
        }

        return null;
    }

    /**
     * The argument order errors of zend_compile_args(); in a call that creates no closure, a placeholder is a value
     *
     * @param array<Arg|ArgPlaceholder|VariadicPlaceholder> $raw_args
     * @psalm-capabilities read-props
     */
    private static function getArgumentsError(array $raw_args, bool $is_partial): ?string
    {
        $has_named = false;
        $has_unpack = false;
        $has_variadic_placeholder = false;

        foreach ($raw_args as $arg) {
            if ($arg instanceof Arg && $arg->unpack) {
                if ($has_named) {
                    return 'Cannot use argument unpacking after named arguments';
                }

                if ($is_partial) {
                    return 'Cannot combine partial application and unpacking';
                }

                $has_unpack = true;
            } elseif (!$arg instanceof VariadicPlaceholder && $arg->name !== null) {
                if ($has_variadic_placeholder) {
                    return 'Variadic placeholder must be last';
                }

                $has_named = true;
            } else {
                $is_variadic_placeholder = $is_partial && $arg instanceof VariadicPlaceholder;

                if ($has_unpack) {
                    return 'Cannot use positional argument after argument unpacking';
                }

                if ($has_named && !$is_variadic_placeholder) {
                    return 'Cannot use positional argument after named argument';
                }

                if ($has_variadic_placeholder) {
                    return $is_variadic_placeholder
                        ? 'Variadic placeholder may only appear once'
                        : 'Variadic placeholder must be last';
                }

                $has_variadic_placeholder = $is_variadic_placeholder;
            }
        }

        return null;
    }

    /**
     * Whether a nullsafe operator short-circuits the chain, as PHP's compiler sees it: parentheses don't end the
     * chain, and the class of a static call or property within it is part of it
     *
     * @psalm-capabilities read-props
     */
    private static function isShortCircuited(Expr $expr): bool
    {
        while (true) {
            if ($expr instanceof NullsafeMethodCall || $expr instanceof NullsafePropertyFetch) {
                return true;
            }

            if ($expr instanceof MethodCall || $expr instanceof PropertyFetch || $expr instanceof ArrayDimFetch) {
                $expr = $expr->var;
            } elseif (($expr instanceof StaticCall || $expr instanceof StaticPropertyFetch)
                && $expr->class instanceof Expr
            ) {
                $expr = $expr->class;
            } else {
                return false;
            }
        }
    }
}
