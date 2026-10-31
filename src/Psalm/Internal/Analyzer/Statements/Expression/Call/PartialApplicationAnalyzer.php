<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser\Node\Arg;
use PhpParser\Node\ArgPlaceholder;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\VariadicPlaceholder;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodCallReturnTypeFetcher;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\InvalidNamedArgument;
use Psalm\Issue\ParseError;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Expr\VirtualStaticCall;
use Psalm\Node\VirtualArg;
use Psalm\Node\VirtualIdentifier;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_key_last;
use function count;
use function is_string;
use function str_starts_with;
use function strtolower;

/**
 * Partial function application (PHP 8.6): `f(1, ?)`, `$o->m(x: ?)`, `C::m(1, ...)`. The callee is not called: the
 * expression is a Closure with the signature of the callee's first-class callable form without the bound parameters.
 *
 * @internal
 */
final class PartialApplicationAnalyzer
{
    public static function isPartialApplication(CallLike $stmt): bool
    {
        return $stmt->isPartialFunctionApplication() && !$stmt->isFirstClassCallable();
    }

    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        CallLike $stmt,
        Context $context,
    ): bool {
        if ($stmt instanceof FuncCall || $stmt instanceof MethodCall || $stmt instanceof StaticCall) {
            $error = self::hasNullsafeReceiver($stmt)
                ? 'Cannot combine nullsafe operator with Closure creation'
                : self::getSyntaxError($stmt);

            if ($error === null) {
                // Below PHP 8.6 this is a compile error; still analyze for tooling.
                if ($statements_analyzer->getCodebase()->analysis_php_version_id < 8_06_00) {
                    self::report($statements_analyzer, new ParseError(
                        'Partial function application requires PHP 8.6',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ));
                }

                return self::analyzeCall($statements_analyzer, $stmt, $context);
            }
        } else {
            $error = $stmt instanceof New_
                ? 'Cannot create Closure for new expression'
                : 'Cannot combine nullsafe operator with Closure creation';
        }

        // compile errors: no closure can be made
        self::report($statements_analyzer, new ParseError(
            $error,
            new CodeLocation($statements_analyzer->getSource(), $stmt),
        ));
        $statements_analyzer->node_data->setType($stmt, Type::getClosure());

        return true;
    }

    private static function report(StatementsAnalyzer $statements_analyzer, CodeIssue $issue): void
    {
        IssueBuffer::maybeAdd($issue, $statements_analyzer->getSuppressedIssues());
    }

    /**
     * The compile error PHP 8.6 gives for the placement of the placeholders, arguments and unpacking, if any.
     */
    private static function getSyntaxError(CallLike $stmt): ?string
    {
        $raw_args = $stmt->getRawArgs();
        $last_key = array_key_last($raw_args);
        $variadic_placeholders = 0;
        $variadic_placeholder_is_last = true;
        $has_unpacking = self::containsUnpack($raw_args);
        $has_named_arg = false;
        $has_positional_arg_after_named = false;

        foreach ($raw_args as $key => $arg) {
            if ($arg instanceof VariadicPlaceholder) {
                $variadic_placeholders++;
                $variadic_placeholder_is_last = $variadic_placeholder_is_last && $key === $last_key;
            } elseif ($arg->name !== null) {
                $has_named_arg = true;
            } else {
                $has_positional_arg_after_named = $has_positional_arg_after_named || $has_named_arg;
            }
        }

        return match (true) {
            $variadic_placeholders > 1 => 'Variadic placeholder may only appear once',
            !$variadic_placeholder_is_last => 'Variadic placeholder must be last',
            $has_unpacking => 'Cannot combine partial application and unpacking',
            $has_positional_arg_after_named => 'Cannot use positional argument after named argument',
            default => null,
        };
    }

    /**
     * @param array<Arg|VariadicPlaceholder|ArgPlaceholder> $raw_args
     * @psalm-capabilities read-props
     */
    private static function containsUnpack(array $raw_args): bool
    {
        foreach ($raw_args as $arg) {
            if ($arg instanceof Arg && $arg->unpack) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the nullsafe operator short-circuits the call: it does the chain of property fetches, method calls and
     * array fetches that it is at the end of, up to a parenthesis.
     */
    private static function hasNullsafeReceiver(FuncCall|MethodCall|StaticCall $stmt): bool
    {
        $node = match (true) {
            $stmt instanceof MethodCall => $stmt->var,
            $stmt instanceof StaticCall => $stmt->class,
            default => $stmt->name,
        };

        while ($node instanceof Expr && $node->getStartFilePos() === $stmt->getStartFilePos()) {
            if ($node instanceof NullsafePropertyFetch || $node instanceof NullsafeMethodCall) {
                return true;
            }

            $node = $node instanceof PropertyFetch || $node instanceof MethodCall || $node instanceof ArrayDimFetch
                ? $node->var
                : null;
        }

        return false;
    }

    private static function analyzeCall(
        StatementsAnalyzer $statements_analyzer,
        FuncCall|MethodCall|StaticCall $stmt,
        Context $context,
    ): bool {
        // the callee is resolved, and its expressions evaluated, by analysing the call as a first-class callable
        $callable_form = match (true) {
            $stmt instanceof FuncCall => new VirtualFuncCall(
                $stmt->name,
                [new VariadicPlaceholder()],
                $stmt->getAttributes(),
            ),
            $stmt instanceof MethodCall => new VirtualMethodCall(
                $stmt->var,
                $stmt->name,
                [new VariadicPlaceholder()],
                $stmt->getAttributes(),
            ),
            default => new VirtualStaticCall(
                $stmt->class,
                $stmt->name,
                [new VariadicPlaceholder()],
                $stmt->getAttributes(),
            ),
        };

        if (ExpressionAnalyzer::analyze($statements_analyzer, $callable_form, $context) === false) {
            return false;
        }

        $callee_type = $statements_analyzer->node_data->getType($callable_form);
        $callee = $callee_type !== null && $callee_type->isSingle() ? $callee_type->getSingleAtomic() : null;
        $raw_args = $stmt->getRawArgs();

        if (($callee instanceof TClosure || $callee instanceof TCallable) && $callee->params !== null) {
            $callee_params = $callee->params;
            [$closure_params, $bound_args] = self::getSignature(
                $statements_analyzer,
                $stmt,
                $raw_args,
                $callee_params,
                $callee->callable_id,
            );

            if (ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $bound_args,
                $callee_params,
                null,
                true,
                $context,
            ) === false) {
                return false;
            }

            foreach ($bound_args as $arg) {
                // a variable bound to a parameter by reference is there from now on
                $param = $arg->name !== null ? self::getParamByName($callee_params, $arg->name->name) : null;

                if ($param !== null
                    && $param->by_ref
                    && $arg->value instanceof Variable
                    && is_string($arg->value->name)
                    && !isset($context->vars_in_scope['$' . $arg->value->name])
                ) {
                    $context->vars_in_scope['$' . $arg->value->name] = Type::getMixed();
                }
            }

            $return_type = $callee->return_type;
            $receiver_class = self::getReceiverClass($statements_analyzer, $context, $stmt);

            if ($receiver_class !== null) {
                // `static` and `self` are the class of the receiver
                $codebase = $statements_analyzer->getCodebase();
                $return_type = $return_type !== null
                    ? self::expandStatic($codebase, $receiver_class, $return_type)
                    : null;

                $expanded_params = [];

                foreach ($closure_params ?? [] as $closure_param) {
                    $expanded_params[] = $closure_param->type !== null
                        ? $closure_param->setType(self::expandStatic($codebase, $receiver_class, $closure_param->type))
                        : $closure_param;
                }

                $closure_params = $closure_params !== null ? $expanded_params : null;
            }

            $callable_id = $callee->callable_id ?? self::getCallableIdOfCalledValue($statements_analyzer, $stmt);

            if ($callable_id !== null) {
                // what is bound reaches the parameters of the function as what a call gives them does
                $return_node = FunctionCallReturnTypeFetcher::taintBoundArguments(
                    $statements_analyzer,
                    $callable_id,
                    $bound_args,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                );

                if ($return_node !== null && $return_type !== null) {
                    // what the function returns of it is what the closure returns
                    $return_type = $return_type->addParentNodes([$return_node->id => $return_node]);
                }
            }

            if ($stmt instanceof StaticCall && $receiver_class !== null) {
                self::checkStaticInvocation($statements_analyzer, $stmt, $context, $receiver_class);
            }

            if (!$stmt instanceof FuncCall && $stmt->name instanceof Identifier && $receiver_class !== null) {
                self::taintMethodCall(
                    $statements_analyzer,
                    $context,
                    $stmt,
                    $receiver_class,
                    $bound_args,
                    $return_type,
                );
            }

            $statements_analyzer->node_data->setType(
                $stmt,
                $closure_params !== null && self::isResolved($statements_analyzer, $callee, $callable_id)
                    ? new Union([
                        new TClosure(
                            $closure_params,
                            $return_type,
                            $callee->purity,
                            callable_id: $callable_id,
                        ),
                    ])
                    : Type::getClosure(),
            );

            return true;
        }

        // nothing to build a signature from: a callee that is one of several, or about which nothing is known
        $bound_args = [];

        foreach ($raw_args as $arg) {
            if ($arg instanceof Arg) {
                $bound_args[] = $arg;
            }
        }

        if (ArgumentsAnalyzer::analyze($statements_analyzer, $bound_args, null, null, true, $context) === false) {
            return false;
        }

        $statements_analyzer->node_data->setType($stmt, Type::getClosure());

        return true;
    }

    /**
     * The id of the function of the closure that is called, if it is a first-class callable or a partial application.
     *
     * @return non-empty-lowercase-string|null
     * @psalm-capabilities read-props
     */
    private static function getCallableIdOfCalledValue(
        StatementsAnalyzer $statements_analyzer,
        FuncCall|MethodCall|StaticCall $stmt,
    ): ?string {
        $type = $stmt instanceof FuncCall && $stmt->name instanceof Expr
            ? $statements_analyzer->node_data->getType($stmt->name)
            : null;
        $called = $type !== null && $type->isSingle() ? $type->getSingleAtomic() : null;

        return $called instanceof TClosure || $called instanceof TCallable ? $called->callable_id : null;
    }

    private static function expandStatic(Codebase $codebase, string $class, Union $type): Union
    {
        $storage = $codebase->classlikes->getStorageFor($class);
        $final = $storage !== null && ($storage->final || $storage->is_enum);

        return TypeExpander::expandUnion(
            $codebase,
            $type,
            $class,
            new TNamedObject($class, true, $final),
            null,
            final: $final,
        );
    }

    /**
     * The class that `static` and `self` are in what the method takes and returns.
     */
    private static function getReceiverClass(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        FuncCall|MethodCall|StaticCall $stmt,
    ): ?string {
        if ($stmt instanceof MethodCall) {
            $type = $statements_analyzer->node_data->getType($stmt->var);
            $receiver = $type !== null && $type->isSingle() ? $type->getSingleAtomic() : null;

            return $receiver instanceof TNamedObject ? $receiver->value : null;
        }

        if (!$stmt instanceof StaticCall) {
            return null;
        }

        if (!$stmt->class instanceof Name) {
            // a class name or an object in a variable
            $type = $statements_analyzer->node_data->getType($stmt->class);
            $class = $type !== null && $type->isSingle() ? $type->getSingleAtomic() : null;

            return $class instanceof TNamedObject || $class instanceof TLiteralClassString ? $class->value : null;
        }

        return match ($stmt->class->toLowerString()) {
            'self', 'static' => $context->self,
            'parent' => $context->parent,
            default => (string) $stmt->class->getAttribute('resolvedName'),
        };
    }

    /**
     * What the method does with the arguments bound and returns of them, as for a call.
     *
     * @param list<Arg> $bound_args
     */
    private static function taintMethodCall(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        MethodCall|StaticCall $stmt,
        string $receiver_class,
        array $bound_args,
        ?Union &$return_type,
    ): void {
        if (!$stmt->name instanceof Identifier) {
            return;
        }

        $codebase = $statements_analyzer->getCodebase();
        $method_id = new MethodIdentifier($receiver_class, strtolower($stmt->name->name));
        $cased_method_id = $codebase->methods->getCasedMethodId($method_id);
        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);
        $storage = $declaring_method_id !== null ? $codebase->methods->getStorage($declaring_method_id) : null;

        if ($storage !== null && $cased_method_id !== '') {
            FunctionCallReturnTypeFetcher::taintBoundArguments(
                $statements_analyzer,
                $cased_method_id,
                $bound_args,
                // where the call is specialized: its name for a method, as the return type of a call has it
                new CodeLocation($statements_analyzer->getSource(), $stmt instanceof MethodCall ? $stmt->name : $stmt),
                $storage,
            );
        }

        if ($return_type === null) {
            return;
        }

        if ($stmt instanceof StaticCall) {
            StaticCallAnalyzer::taintReturnType(
                $statements_analyzer,
                new VirtualStaticCall($stmt->class, $stmt->name, $bound_args, $stmt->getAttributes()),
                $method_id,
                $cased_method_id,
                $return_type,
                $storage,
                null,
                $context,
            );
        } elseif ($declaring_method_id !== null) {
            MethodCallReturnTypeFetcher::taintMethodCallResult(
                $statements_analyzer,
                $return_type,
                $stmt->name,
                $stmt->var,
                $bound_args,
                $method_id,
                $declaring_method_id,
                $cased_method_id,
                $context,
            );
        }
    }

    /**
     * A method that is not static is not called with the name of its class, as a call checks it.
     */
    private static function checkStaticInvocation(
        StatementsAnalyzer $statements_analyzer,
        StaticCall $stmt,
        Context $context,
        string $fq_class_name,
    ): void {
        if (!$stmt->name instanceof Identifier) {
            return;
        }

        $codebase = $statements_analyzer->getCodebase();
        $method_id = new MethodIdentifier($fq_class_name, strtolower($stmt->name->name));
        $is_static_context = $statements_analyzer->isStatic();
        $class_keyword = $stmt->class instanceof Name ? $stmt->class->toLowerString() : '';

        if ($codebase->methods->getDeclaringMethodId($method_id)
            && ($class_keyword !== 'parent' || $is_static_context)
            && ($context->self === null
                || $is_static_context
                || !$codebase->classExtends($context->self, $fq_class_name))
        ) {
            MethodAnalyzer::checkStatic(
                $method_id,
                $class_keyword === 'self' || $context->self === $fq_class_name,
                !$is_static_context,
                $codebase,
                new CodeLocation($statements_analyzer, $stmt),
                $statements_analyzer->getSuppressedIssues(),
            );
        }
    }

    /**
     * The parameters of the closure (null if the placeholders are wrong) and the arguments given to the callee, named
     * after their parameters. The number and the names of the arguments are checked.
     *
     * @param array<Arg|VariadicPlaceholder|ArgPlaceholder> $raw_args
     * @param list<FunctionLikeParameter> $callee_params
     * @return array{list<FunctionLikeParameter>|null, list<Arg>}
     */
    private static function getSignature(
        StatementsAnalyzer $statements_analyzer,
        FuncCall|MethodCall|StaticCall $stmt,
        array $raw_args,
        array $callee_params,
        ?string $callable_id,
    ): array {
        $has_variadic_callee = $callee_params !== [] && $callee_params[count($callee_params) - 1]->is_variadic;
        $closure_params = [];
        $bound_args = [];
        $bound_params = [];
        $supplied_names = [];
        $taken_names = [];
        $position = 0;
        $variadic_position = 0;
        $has_open_rest = false;
        $is_valid = true;

        foreach ($callee_params as $param) {
            $taken_names[$param->name] = true;
        }

        foreach ($raw_args as $arg) {
            if ($arg instanceof VariadicPlaceholder) {
                $has_open_rest = true;

                continue;
            }

            $name = $arg->name?->name;
            $param = $name === null
                ? self::getParamAtPosition($callee_params, $position)
                : self::getParamByName($callee_params, $name);

            if ($name === null) {
                if ($param === null) {
                    self::report($statements_analyzer, new TooManyArguments(
                        'Too many arguments for the partial application - expecting ' . count($callee_params),
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ));
                    $is_valid = false;
                } elseif (!$param->is_variadic) {
                    $bound_params[$position] = true;

                    if ($param->name !== '') {
                        $bound_params[$param->name] = true;
                    }
                }

                $position++;
            } elseif ($arg instanceof ArgPlaceholder
                ? $param === null || $param->is_variadic
                : $param === null && !$has_variadic_callee
            ) {
                self::report($statements_analyzer, new InvalidNamedArgument(
                    'Parameter $' . $name . ' does not exist or is variadic',
                    new CodeLocation($statements_analyzer->getSource(), $arg instanceof ArgPlaceholder ? $arg : $stmt),
                ));
                $is_valid = false;
            } elseif (isset($supplied_names[$name]) || isset($bound_params[$name])) {
                self::report($statements_analyzer, new InvalidNamedArgument(
                    'Named parameter $' . $name . ' overwrites previous argument',
                    new CodeLocation($statements_analyzer->getSource(), $arg instanceof ArgPlaceholder ? $arg : $stmt),
                ));
                $is_valid = false;
            } else {
                $supplied_names[$name] = true;

                if ($param !== null && !$param->is_variadic) {
                    $bound_params[$name] = true;
                }
            }

            if ($arg instanceof Arg) {
                // named after the parameter, as its position is not what it is among the arguments given
                $bound_args[] = $param !== null && $name === null && $param->name !== ''
                    ? new VirtualArg(
                        $arg->value,
                        $arg->byRef,
                        false,
                        $arg->getAttributes(),
                        new VirtualIdentifier($param->name),
                    )
                    : $arg;
            } elseif ($param !== null && !($name !== null && $param->is_variadic)) {
                // the parameter of the closure that a placeholder makes: always required, whatever it is of the callee
                $closure_param_name = $param->name;

                while ($param->is_variadic
                    && $closure_param_name !== ''
                    && ($closure_param_name === $param->name || isset($taken_names[$closure_param_name]))
                ) {
                    $closure_param_name = $param->name . $variadic_position++;
                }

                $taken_names[$closure_param_name] = true;
                $closure_param = clone $param;
                $closure_param->name = $closure_param_name;
                $closure_param->is_optional = false;
                $closure_param->is_variadic = false;
                $closure_param->default_type = null;
                $closure_params[] = $closure_param;
            }
        }

        foreach ($callee_params as $i => $param) {
            if (isset($bound_params[$i]) || isset($bound_params[$param->name])) {
                continue;
            }

            if ($has_open_rest) {
                $closure_params[] = $param;
            } elseif (!$param->is_optional
                && !$param->is_variadic
                && self::isRequiredByEveryVariant($callable_id, $i)
            ) {
                self::report($statements_analyzer, new TooFewArguments(
                    'Too few arguments for the partial application - expecting '
                        . ($param->name !== '' ? '$' . $param->name : 'argument ' . ($i + 1)) . ' to be passed',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ));
            }
        }

        return [$is_valid ? $closure_params : null, $bound_args];
    }

    /**
     * Whether the parameter at the position is required by every signature that the call map has for the function.
     */
    private static function isRequiredByEveryVariant(?string $callable_id, int $position): bool
    {
        $variants = $callable_id !== null ? InternalCallMapHandler::getCallablesFromCallMap($callable_id) : null;

        foreach ($variants ?? [] as $variant) {
            $required = 0;

            foreach ($variant->params ?? [] as $param) {
                $required += !$param->is_optional && !$param->is_variadic ? 1 : 0;
            }

            if ($required <= $position) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the closure can have the signature of the callee: not with template types (those of a function of the
     * code are already replaced in its first-class callable) or unnamed parameters (a callable of a docblock).
     *
     * @param non-empty-lowercase-string|null $callable_id
     */
    private static function isResolved(
        StatementsAnalyzer $statements_analyzer,
        TClosure|TCallable $callee,
        ?string $callable_id,
    ): bool {
        $types = $callee->return_type !== null ? [$callee->return_type] : [];

        foreach ($callee->params ?? [] as $param) {
            if ($param->name === '') {
                return false;
            }

            if ($param->type !== null) {
                $types[] = $param->type;
            }
        }

        foreach ($types as $type) {
            foreach ($type->getTemplateTypes() as $template_type) {
                // those generated for the parameters of internal functions are not the callee's own
                if (!str_starts_with($template_type->param_name, 'TGenerated')) {
                    return false;
                }
            }
        }

        $functions = $statements_analyzer->getCodebase()->functions;

        if ($callable_id === null || !$functions->isDeclaredInCode($callable_id)) {
            return true;
        }

        try {
            $storage = $functions->getStorage($statements_analyzer, $callable_id);
        } catch (UnexpectedValueException) {
            return true;
        }

        return $storage->template_types === null || $storage->template_types === [];
    }

    /**
     * The parameter an argument at the position is passed to: the variadic one past the end.
     *
     * @param list<FunctionLikeParameter> $params
     * @psalm-capabilities read-props
     */
    private static function getParamAtPosition(array $params, int $position): ?FunctionLikeParameter
    {
        if (isset($params[$position])) {
            return $params[$position];
        }

        $last_param = $params !== [] ? $params[count($params) - 1] : null;

        return $last_param !== null && $last_param->is_variadic ? $last_param : null;
    }

    /**
     * @param list<FunctionLikeParameter> $params
     * @psalm-capabilities read-props
     */
    private static function getParamByName(array $params, string $name): ?FunctionLikeParameter
    {
        foreach ($params as $param) {
            if ($param->name === $name) {
                return $param;
            }
        }

        return null;
    }
}
