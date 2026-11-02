<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser\Node\Arg;
use PhpParser\Node\ArgPlaceholder;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\VariadicPlaceholder;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\ArrayAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\InstancePropertyAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\StaticMethod\ExistingAtomicStaticCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\InstancePropertyFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\CliOutput;
use Psalm\Internal\Codebase\ConditionalTaintSinks;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\InvalidNamedArgument;
use Psalm\Issue\ParseError;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\PartialApplication;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_pop;
use function count;
use function end;
use function in_array;
use function strtolower;

/**
 * Analyzes a partial function application (`f(1, ?)`): the callee as the first-class callable the closure is
 * created from (nothing is called), the bound arguments once, and the closure type from the parameters left open.
 *
 * @internal
 */
final class PartialApplicationAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PartialApplication $stmt,
        Context $context,
    ): bool {
        $codebase = $statements_analyzer->getCodebase();
        $code_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

        self::checkPhpVersion($statements_analyzer, $stmt);

        $compile_error = self::getCompileError($stmt);

        if ($compile_error !== null) {
            IssueBuffer::maybeAdd(
                new ParseError($compile_error, $code_location),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        $closure = null;

        if ($stmt->call instanceof New_) {
            if ($stmt->call->class instanceof Expr
                && ExpressionAnalyzer::analyze($statements_analyzer, $stmt->call->class, $context) === false
            ) {
                return false;
            }
        } else {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->call, $context) === false) {
                return false;
            }

            $callee_type = $statements_analyzer->node_data->getType($stmt->call);

            if ($compile_error === null
                && $callee_type
                && $callee_type->isSingle()
                && ($atomic = $callee_type->getSingleAtomic()) instanceof TClosure
                && $atomic->params !== null
            ) {
                $closure = $atomic;
            }
        }

        $binding = null;
        $method_id = null;
        $has_one_signature = false;

        if ($closure) {
            $method_id = self::getMethodId($statements_analyzer, $stmt, $context);
            // the diagnostics of the application name the method where it is declared
            $declaring_method_id = $method_id !== null
                ? $codebase->methods->getDeclaringMethodId($method_id, true)
                : null;
            $has_one_signature = self::hasOneSignature($statements_analyzer, $closure);
            $binding = self::bind(
                $statements_analyzer,
                $stmt,
                $closure,
                $closure->callable_id ?? ($declaring_method_id !== null ? (string) $declaring_method_id : null),
                $closure->callable_id
                    ?? ($declaring_method_id !== null
                        ? $codebase->methods->getCasedMethodId($declaring_method_id)
                        : 'the closure'),
                $has_one_signature,
                $code_location,
            );
        }

        // as a call would, a builtin's reference parameter gets no null check (see ArgumentsAnalyzer)
        $callee_id = $closure?->callable_id;
        $checks_null_reference = $callee_id === null || !InternalCallMapHandler::inCallMap($callee_id);

        foreach ($stmt->args as $offset => $arg) {
            if ($arg instanceof Arg
                && self::analyzeBoundArg(
                    $statements_analyzer,
                    $arg,
                    $binding['bound'][$offset] ?? null,
                    $checks_null_reference,
                    $context,
                ) === false
            ) {
                return false;
            }
        }

        if ($closure && $binding && $has_one_signature) {
            self::checkBoundArgs(
                $statements_analyzer,
                $stmt,
                $closure,
                $method_id,
                $binding['bound'],
                $binding['open'],
                $code_location,
                $context,
            );
        }

        $type = Type::getClosure();

        if ($closure
            && $binding
            && $binding['params'] !== null
            && !self::hasTemplates($statements_analyzer, $closure)
        ) {
            $return_type = $closure->return_type;
            $static_class_type = self::getStaticClassType($statements_analyzer, $stmt);

            if ($return_type && $static_class_type) {
                $return_type = TypeExpander::expandUnion(
                    $codebase,
                    $return_type,
                    $static_class_type->value,
                    $static_class_type,
                    null,
                    true,
                    false,
                    $codebase->classlike_storage_provider->has($static_class_type->value)
                        && $codebase->classlike_storage_provider->get($static_class_type->value)->final,
                );
            }

            $type = new Union([new TClosure($binding['params'], $return_type, $closure->purity)]);
        }

        $statements_analyzer->node_data->setType($stmt, $type);

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            $codebase->analyzer->addNodeType($statements_analyzer->getFilePath(), $stmt, $type->getId());
        }

        return true;
    }

    public static function checkPhpVersion(StatementsAnalyzer $statements_analyzer, PartialApplication $stmt): void
    {
        if ($statements_analyzer->getCodebase()->analysis_php_version_id < 8_06_00) {
            IssueBuffer::maybeAdd(
                new ParseError(
                    'Partial function application requires PHP 8.6',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }
    }

    /**
     * @psalm-capabilities read-props
     */
    private static function getCompileError(PartialApplication $stmt): ?string
    {
        $call = $stmt->call;

        if ($call instanceof New_) {
            return 'Cannot create Closure for new expression';
        }

        // PHP checks the object chain of a method call only: the class of a static call may be a nullsafe expression
        if ($call instanceof NullsafeMethodCall
            || ($call instanceof MethodCall && self::isShortCircuited($call->var))
        ) {
            return 'Cannot combine nullsafe operator with Closure creation';
        }

        $has_named = false;
        $has_variadic_placeholder = false;

        foreach ($stmt->args as $arg) {
            if ($has_variadic_placeholder) {
                return $arg instanceof VariadicPlaceholder
                    ? 'Variadic placeholder may only appear once'
                    : 'Variadic placeholder must be last';
            }

            if ($arg instanceof VariadicPlaceholder) {
                $has_variadic_placeholder = true;
            } elseif ($arg instanceof Arg && $arg->unpack) {
                return 'Cannot combine partial application and unpacking';
            } elseif ($arg->name !== null) {
                $has_named = true;
            } elseif ($has_named) {
                return 'Cannot use positional argument after named argument';
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

    /**
     * The method the first-class callable of a method call refers to, when its class is known
     */
    private static function getMethodId(
        StatementsAnalyzer $statements_analyzer,
        PartialApplication $stmt,
        Context $context,
    ): ?MethodIdentifier {
        $call = $stmt->call;

        if (!($call instanceof MethodCall || $call instanceof StaticCall) || !$call->name instanceof Identifier) {
            return null;
        }

        if ($call instanceof MethodCall) {
            $fq_class_name = self::getStaticClassType($statements_analyzer, $stmt)?->value;
        } elseif ($call->class instanceof Name) {
            $fq_class_name = match ($call->class->toLowerString()) {
                'self', 'static' => $context->self,
                'parent' => $statements_analyzer->getParentFQCLN(),
                default => ClassLikeAnalyzer::getFQCLNFromNameObject($call->class, $statements_analyzer->getAliases()),
            };
        } else {
            return null;
        }

        $codebase = $statements_analyzer->getCodebase();
        $fq_class_name = $fq_class_name !== null ? $codebase->classlikes->getUnAliasedName($fq_class_name) : null;

        if ($fq_class_name === null || !$codebase->classlike_storage_provider->has($fq_class_name)) {
            return null;
        }

        $method_id = new MethodIdentifier($fq_class_name, strtolower($call->name->name));

        return $codebase->methods->getDeclaringMethodId($method_id, true) !== null ? $method_id : null;
    }

    /**
     * Of a callmap function with several signatures, which one applies isn't known (a stub gives it one)
     */
    private static function hasOneSignature(StatementsAnalyzer $statements_analyzer, TClosure $closure): bool
    {
        return !($closure->callable_id !== null
            && InternalCallMapHandler::inCallMap($closure->callable_id)
            && !$statements_analyzer->getCodebase()->functions->hasStubbedFunction($closure->callable_id)
            && count(InternalCallMapHandler::getCallablesFromCallMap($closure->callable_id) ?? []) > 1);
    }

    /**
     * Maps the arguments and placeholders to the parameters of the callee, following the RFC: placeholders become
     * required parameters of the closure (positional ones first, then named ones in the order written), and `...`
     * keeps the parameters nothing else covers.
     *
     * @return array{
     *     params: ?list<FunctionLikeParameter>,
     *     bound: array<int, FunctionLikeParameter>,
     *     open: array<int, true>,
     * } the closure parameters (null when the application fails), the parameter each bound argument goes to, by
     *     offset, and the offsets of the parameters the closure's caller gives
     */
    private static function bind(
        StatementsAnalyzer $statements_analyzer,
        PartialApplication $stmt,
        TClosure $closure,
        ?string $callee_id,
        string $callee_name,
        bool $report,
        CodeLocation $code_location,
    ): array {
        $params = $closure->params ?? [];
        $variadic = $params !== [] && end($params)->is_variadic ? array_pop($params) : null;
        $fixed_count = count($params);

        $positional_placeholders = [];
        $named_placeholders = [];
        $covered = [];
        $extra_names = [];
        $bound = [];
        $open = [];
        $variadic_placeholder_count = 0;
        $has_variadic_placeholder = false;
        $failed = !$report;
        $suppressed = $statements_analyzer->getSuppressedIssues();

        foreach ($stmt->args as $offset => $arg) {
            if ($arg instanceof VariadicPlaceholder) {
                $has_variadic_placeholder = true;

                continue;
            }

            $is_placeholder = $arg instanceof ArgPlaceholder;

            if ($arg->name === null) {
                if ($offset < $fixed_count) {
                    $covered[$offset] = true;

                    if ($is_placeholder) {
                        $open[$offset] = true;
                        $positional_placeholders[] = self::makeRequired($params[$offset]);
                    } else {
                        $bound[$offset] = $params[$offset];
                    }
                } elseif ($variadic) {
                    if ($is_placeholder) {
                        $open[$fixed_count] = true;
                        $positional_placeholders[] = self::makeRequired(
                            $variadic,
                            $variadic->name . $variadic_placeholder_count++,
                        );
                    } else {
                        $bound[$offset] = $variadic;
                    }
                } else {
                    $failed = true;

                    if ($report) {
                        IssueBuffer::maybeAdd(
                            new TooManyArguments(
                                'Too many arguments for partial application of ' . $callee_name
                                    . ' - expecting at most ' . $fixed_count,
                                $code_location,
                                $callee_id,
                            ),
                            $suppressed,
                        );
                    }

                    break;
                }

                continue;
            }

            $name = $arg->name->name;
            $param_offset = null;

            foreach ($params as $i => $param) {
                if ($param->name === $name) {
                    $param_offset = $i;
                    break;
                }
            }

            $error = null;

            if ($param_offset !== null) {
                if (isset($covered[$param_offset])) {
                    $error = 'Named parameter $' . $name . ' overwrites a previous argument or placeholder';
                } else {
                    $covered[$param_offset] = true;

                    if ($is_placeholder) {
                        $open[$param_offset] = true;
                        $named_placeholders[] = self::makeRequired($params[$param_offset]);
                    } else {
                        $bound[$offset] = $params[$param_offset];
                    }
                }
            } elseif ($is_placeholder) {
                $error = 'Cannot use named placeholder for unknown or variadic parameter $' . $name;
            } elseif (!$variadic) {
                $error = 'Parameter $' . $name . ' does not exist on ' . $callee_name;
            } elseif (isset($extra_names[$name])) {
                $error = 'Named parameter $' . $name . ' overwrites a previous argument';
            } else {
                $extra_names[$name] = true;
                $bound[$offset] = $variadic;
            }

            if ($error !== null) {
                $failed = true;

                if ($report) {
                    IssueBuffer::maybeAdd(
                        new InvalidNamedArgument($error, $code_location, $callee_id),
                        $suppressed,
                    );
                }
            }
        }

        $closure_params = [...$positional_placeholders, ...$named_placeholders];

        foreach ($params as $offset => $param) {
            if (isset($covered[$offset])) {
                continue;
            }

            if ($has_variadic_placeholder) {
                $open[$offset] = true;
                $closure_params[] = $param;
            } elseif (!$param->is_optional && !$failed) {
                $failed = true;

                IssueBuffer::maybeAdd(
                    new TooFewArguments(
                        'Too few arguments for partial application of ' . $callee_name
                            . ' - expecting ' . $param->name . ' to be passed',
                        $code_location,
                        $callee_id,
                    ),
                    $suppressed,
                );
            }
        }

        if ($has_variadic_placeholder && $variadic) {
            $open[$fixed_count] = true;
            $closure_params[] = $variadic;
        }

        return ['params' => $failed ? null : $closure_params, 'bound' => $bound, 'open' => $open];
    }

    /**
     * @psalm-pure
     */
    private static function makeRequired(FunctionLikeParameter $param, ?string $name = null): FunctionLikeParameter
    {
        $param = clone $param;
        $param->name = $name ?? $param->name;
        $param->is_optional = false;
        $param->is_variadic = false;
        $param->default_type = null;

        return $param;
    }

    /**
     * Templates of the callee stay unbound: the closure is a plain Closure until they are inferred. A builtin's
     * signature is the one its first-class callable has, with templates expanded to their bounds
     */
    private static function hasTemplates(StatementsAnalyzer $statements_analyzer, TClosure $closure): bool
    {
        foreach ([...$closure->params ?? [], $closure] as $part) {
            $type = $part instanceof TClosure ? $part->return_type : $part->type;

            if ($type && $type->getTemplateTypes() !== []) {
                return true;
            }
        }

        if ($closure->callable_id === null || InternalCallMapHandler::inCallMap($closure->callable_id)) {
            return false;
        }

        try {
            return (bool) $statements_analyzer->getCodebase()->functions->getStorage(
                $statements_analyzer,
                $closure->callable_id,
            )->template_types;
        } catch (UnexpectedValueException) {
            return false;
        }
    }

    /**
     * The class `static` stands for in the return type, as for a call of the method
     */
    private static function getStaticClassType(
        StatementsAnalyzer $statements_analyzer,
        PartialApplication $stmt,
    ): ?TNamedObject {
        $call = $stmt->call;

        if ($call instanceof MethodCall) {
            $var_type = $statements_analyzer->node_data->getType($call->var);

            return $var_type
                && $var_type->isSingle()
                && ($atomic = $var_type->getSingleAtomic()) instanceof TNamedObject
                ? $atomic
                : null;
        }

        if ($call instanceof StaticCall
            && $call->class instanceof Name
            && !in_array($call->class->toLowerString(), ['self', 'static', 'parent'], true)
        ) {
            return new TNamedObject(
                ClassLikeAnalyzer::getFQCLNFromNameObject($call->class, $statements_analyzer->getAliases()),
            );
        }

        return null;
    }

    /**
     * PHP checks the bound arguments as the arguments of a call when it creates the closure: they get the checks, data
     * flow and sinks of the call
     *
     * @param array<int, FunctionLikeParameter> $bound
     * @param array<int, true> $open the offsets of the parameters the closure's caller gives
     */
    private static function checkBoundArgs(
        StatementsAnalyzer $statements_analyzer,
        PartialApplication $stmt,
        TClosure $closure,
        ?MethodIdentifier $method_id,
        array $bound,
        array $open,
        CodeLocation $code_location,
        Context $context,
    ): void {
        $args = [];

        foreach ($bound as $offset => $_) {
            $arg = $stmt->args[$offset];

            if ($arg instanceof Arg) {
                $args[$offset] = $arg;
            }
        }

        if ($args === []) {
            return;
        }

        $codebase = $statements_analyzer->getCodebase();
        $call = $stmt->call;
        $callee_id = null;
        $storage = null;
        $class_storage = null;
        $class_template_params = null;
        $is_callable_value = false;
        $callable_value_id = null;
        $lhs_type = $call instanceof MethodCall ? self::getStaticClassType($statements_analyzer, $stmt) : null;

        if ($call instanceof StaticCall && $call->class instanceof Name) {
            StaticCallAnalyzer::markArgsOfACallNamingItsClass($statements_analyzer, $call->class, $args, $context);
        } elseif ($call instanceof FuncCall && $call->name instanceof Expr) {
            $callee_type = $statements_analyzer->node_data->getType($call->name);
            $callee_atomic = $callee_type && $callee_type->isSingle() ? $callee_type->getSingleAtomic() : null;

            // as calls of them (see FunctionCallAnalyzer): a function name is called like the function, an object
            // through its __invoke(), and a closure or callable gets its arguments checked and the data flow of its id
            if ($callee_atomic instanceof TClosure || $callee_atomic instanceof TCallable) {
                $is_callable_value = true;
                $callable_value_id = $callee_atomic->callable_id;
            } elseif ($callee_atomic instanceof TNamedObject
                && strtolower($callee_atomic->value) !== 'closure'
                && $codebase->classlikes->classOrInterfaceExists($callee_atomic->value)
                && $codebase->methods->getDeclaringMethodId(
                    $invoke_method_id = new MethodIdentifier($callee_atomic->value, '__invoke'),
                ) !== null
            ) {
                $method_id = $invoke_method_id;
                $lhs_type = $callee_atomic;
            } elseif (!$callee_atomic instanceof TLiteralString) {
                $is_callable_value = true;
            }
        }

        // like a call of it, a pseudo method is checked against its parameters only
        if ($method_id !== null && $codebase->methods->getDeclaringMethodId($method_id) !== null) {
            $callee_id = $method_id;
            $class_storage = $codebase->classlike_storage_provider->get($method_id->fq_class_name);
            $declaring_method_id = $class_storage->declaring_method_ids[$method_id->method_name] ?? null;
            $storage = $declaring_method_id ? $codebase->methods->getStorage($declaring_method_id) : null;

            if ($lhs_type) {
                $class_template_params = ClassTemplateParamCollector::collect(
                    $codebase,
                    $codebase->methods->getClassLikeStorageForMethod($method_id),
                    $class_storage,
                    $method_id->method_name,
                    $lhs_type,
                    $call instanceof MethodCall && $call->var instanceof Variable && $call->var->name === 'this',
                );
            } elseif ($call instanceof StaticCall) {
                $class_template_params = ExistingAtomicStaticCallAnalyzer::collectClassTemplateParams(
                    $statements_analyzer,
                    $call,
                    $context,
                    $class_storage,
                    $method_id,
                    new TNamedObject($method_id->fq_class_name),
                );
            }
        } elseif ($call instanceof FuncCall && !$is_callable_value && $closure->callable_id !== null) {
            $callee_id = $closure->callable_id;
            $functions = $codebase->functions;

            if (!InternalCallMapHandler::inCallMap($callee_id) || $functions->hasStubbedFunction($callee_id)) {
                try {
                    $storage = $functions->getStorage($statements_analyzer, $callee_id);
                } catch (UnexpectedValueException) {
                }
            }

            CliOutput::markPrintedArgs($statements_analyzer, $callee_id, $args, true);
        }

        $template_result = new TemplateResult([], $class_template_params ?? []);

        ArgumentsAnalyzer::checkArgumentsMatch(
            $statements_analyzer,
            $args,
            $callee_id,
            // the declared parameters, as for a call: those of the closure may have the callee's templates replaced
            $storage->params ?? $closure->params ?? [],
            $storage,
            $class_storage,
            $template_result,
            $code_location,
            $context,
            true,
        );

        if ($template_result->template_types) {
            CallAnalyzer::checkTemplateResult(
                $statements_analyzer,
                $template_result,
                $code_location,
                $callee_id !== null ? strtolower((string) $callee_id) : null,
            );
        }

        if ($storage !== null
            && $callee_id !== null
            && ($graph = $statements_analyzer->getTaintFlowGraphWithSuppressed())
        ) {
            $cased_callee_id = $callee_id instanceof MethodIdentifier
                ? $codebase->methods->getCasedMethodId($callee_id)
                : $storage->cased_name ?? $callee_id;

            ConditionalTaintSinks::taintCall(
                $statements_analyzer,
                $graph,
                $storage,
                $args,
                $cased_callee_id,
                $code_location,
                $open,
            );

            // the call specialized for the application never returns here: its exit has no outgoing edge, so that the
            // resolver doesn't leave the callee through every call of it (see TaintFlowResolution::isExitOfEntered())
            if (TaintFlowGraph::isCallSpecialized($graph, $codebase, $storage, $code_location)) {
                $graph->addNode(DataFlowNode::getForMethodReturn(
                    $callee_id instanceof MethodIdentifier
                        ? FunctionLikeAnalyzer::getCasedBodyMethodId($codebase, $callee_id)
                        : $cased_callee_id,
                    $storage,
                    $code_location,
                ));
            }
        }

        // the data flow a call of a callable value gets beside the checks of its arguments
        if ($callable_value_id !== null) {
            FunctionCallReturnTypeFetcher::taintArgsBoundToCallable(
                $statements_analyzer,
                $callable_value_id,
                $args,
                $open,
                $code_location,
            );
        }
    }

    /**
     * @return false|null
     */
    private static function analyzeBoundArg(
        StatementsAnalyzer $statements_analyzer,
        Arg $arg,
        ?FunctionLikeParameter $param,
        bool $checks_null_reference,
        Context $context,
    ): ?bool {
        if ($param && $param->by_ref
            && ($arg->value instanceof Variable
                || $arg->value instanceof ArrayDimFetch
                || $arg->value instanceof PropertyFetch
                || $arg->value instanceof StaticPropertyFetch)
        ) {
            return self::captureReference(
                $statements_analyzer,
                $arg->value,
                $param,
                $checks_null_reference,
                $context,
            );
        }

        $was_inside_call = $context->inside_call;
        $context->inside_call = true;

        $result = ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $context);

        $context->inside_call = $was_inside_call;

        return $result === false ? false : null;
    }

    /**
     * The closure holds a reference to the location: PHP creates it (as null) when it is missing, and the closure may
     * write it whenever it is called. Nothing is written when the closure is created.
     *
     * @return false|null
     */
    private static function captureReference(
        StatementsAnalyzer $statements_analyzer,
        Variable|ArrayDimFetch|PropertyFetch|StaticPropertyFetch $location,
        FunctionLikeParameter $param,
        bool $checks_null_reference,
        Context $context,
    ): ?bool {
        $var_id = ExpressionIdentifier::getExtendedVarId(
            $location,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        );

        if ($location instanceof Variable && $var_id !== null && !isset($context->vars_in_scope[$var_id])) {
            $current_type = Type::getNull();

            // what PHP passes, which the parameter's type is checked against (see checkBoundArgs())
            if ($checks_null_reference) {
                $statements_analyzer->node_data->setType($location, $current_type);
            }
        } else {
            // read like an ordinary by-reference argument: a missing offset is created, not an empty access
            $was_inside_by_ref_offset = $context->inside_by_ref_offset;
            $context->inside_by_ref_offset = $location instanceof ArrayDimFetch;
            $suppress_empty_array_access = !$location instanceof Variable
                && !isset($statements_analyzer->getSuppressedIssues()['EmptyArrayAccess']);

            if ($suppress_empty_array_access) {
                $statements_analyzer->addSuppressedIssues(['EmptyArrayAccess']);
            }

            $result = ExpressionAnalyzer::analyze($statements_analyzer, $location, $context);

            if ($suppress_empty_array_access) {
                $statements_analyzer->removeSuppressedIssues(['EmptyArrayAccess']);
            }

            $context->inside_by_ref_offset = $was_inside_by_ref_offset;

            if ($result === false) {
                return false;
            }

            $current_type = $statements_analyzer->node_data->getType($location) ?? Type::getMixed();
        }

        $param_type = $param->type ?? Type::getMixed();
        // what any later call of the closure may leave in it, whatever it is called with
        $out_type = TypeExpander::expandUnion(
            $statements_analyzer->getCodebase(),
            $param->out_type ?? $param_type,
            null,
            null,
            null,
            true,
            true,
            false,
            false,
            true,
        );
        $type = Type::combineUnionTypes($current_type, $out_type);

        $root = $location;

        while ($root instanceof ArrayDimFetch) {
            $root = $root->var;
        }

        // PHP refuses a reference to a readonly property or to an item of one
        if ($root instanceof PropertyFetch
            && ($prop_name = InstancePropertyFetchAnalyzer::getPropertyName($statements_analyzer, $root)) !== null
            && ($object_type = $statements_analyzer->node_data->getType($root->var)) !== null
        ) {
            foreach ($object_type->getAtomicTypes() as $atomic) {
                if (!$atomic instanceof TNamedObject) {
                    continue;
                }

                $property_id = $atomic->value . '::$' . $prop_name;
                $declaring_class_storage = ArgumentsAnalyzer::getDeclaringClassStorage(
                    $statements_analyzer,
                    $property_id,
                    $prop_name,
                );

                if ($declaring_class_storage !== null) {
                    InstancePropertyAssignmentAnalyzer::checkReadonlyPropertyWrite(
                        $statements_analyzer,
                        $root,
                        $property_id,
                        $declaring_class_storage->properties[$prop_name],
                        $context,
                        $object_type->reference_free && $object_type->allow_mutations,
                    );
                }
            }
        }

        if ($location instanceof Variable) {
            AssignmentAnalyzer::assignByRefParam($statements_analyzer, $location, $param_type, $type, $context, false);
        } elseif ($location instanceof ArrayDimFetch) {
            // PHP creates a missing item, but a property holding the array is not assigned
            if (ArrayAssignmentAnalyzer::updateArrayType(
                $statements_analyzer,
                $location,
                null,
                $type,
                $context,
                true,
                false,
            ) === false) {
                return false;
            }
        } elseif ($var_id !== null) {
            // taking a reference to a property does not assign it
            $context->vars_in_scope[$var_id] = $type;
        }

        // each call of the closure passes the location by reference: the callee may read it
        $root_var_id = ExpressionIdentifier::getExtendedVarId(
            $root,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        );

        if ($root_var_id !== null
            && isset($context->vars_in_scope[$root_var_id])
            && ($variable_use_graph = $statements_analyzer->variable_use_graph)
        ) {
            foreach ($context->vars_in_scope[$root_var_id]->parent_nodes as $parent_node) {
                $variable_use_graph->addPath($parent_node, DataFlowNode::getForVariableUse(), 'variable-use');
            }
        }

        return null;
    }
}
