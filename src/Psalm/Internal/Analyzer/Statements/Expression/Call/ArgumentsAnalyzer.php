<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use InvalidArgumentException;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\AttributesAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\InstancePropertyAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\ConstantTypeResolver;
use Psalm\Internal\Codebase\Functions;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Codebase\InternalTaintSourceMap;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\ReturnTypeProvider\ArrayMapReturnTypeProvider;
use Psalm\Internal\Stubs\Generator\StubsGenerator;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Internal\TypeVisitor\TypeVariableResolver;
use Psalm\Issue\InvalidNamedArgument;
use Psalm\Issue\InvalidPassByReference;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\IssueBuffer;
use Psalm\Node\VirtualArg;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\TaintKind;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_reduce;
use function array_reverse;
use function array_slice;
use function array_values;
use function assert;
use function count;
use function dirname;
use function in_array;
use function is_int;
use function is_string;
use function max;
use function min;
use function reset;
use function str_contains;
use function strtolower;
use function strtoupper;

/**
 * @internal
 */
final class ArgumentsAnalyzer
{
    /**
     * @see self::getByRefFlowInputs()
     * @var array<lowercase-string, array<string, list<string>>>|null
     */
    private static ?array $by_ref_flow_map = null;

    public const ARRAY_FILTERLIKE = [
        'array_filter',
        'array_find',
        'array_find_key',
        'array_any',
        'array_all',
    ];

    /**
     * @param   list<PhpParser\Node\Arg>          $args
     * @param   array<int, FunctionLikeParameter>|null  $function_params
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        array $args,
        ?array $function_params,
        ?string $method_id,
        bool $allow_named_args,
        Context $context,
        ?TemplateResult $template_result = null,
    ): ?bool {
        $last_param = $function_params
            ? $function_params[count($function_params) - 1]
            : null;

        // if this modifies the array type based on further args
        if (in_array($method_id, ['array_push', 'array_unshift'], true)
            && $function_params
            && isset($args[0])
            && isset($args[1])
        ) {
            if (ArrayFunctionArgumentsAnalyzer::handleAddition(
                $statements_analyzer,
                $args,
                $context,
                $method_id,
            ) === false
            ) {
                return false;
            }

            return null;
        }

        if ($method_id === 'array_splice' && $function_params && count($args) > 1) {
            if (ArrayFunctionArgumentsAnalyzer::handleSplice($statements_analyzer, $args, $context) === false) {
                return false;
            }

            return null;
        }

        if ($method_id === 'array_map') {
            $args = array_reverse($args, true);
        }

        foreach ($args as $argument_offset => $arg) {
            if ($arg->value instanceof PhpParser\Node\Expr\Closure
                || $arg->value instanceof PhpParser\Node\Expr\ArrowFunction
            ) {
                // The node may be re-analyzed for another callable target.
                $arg->value->setAttribute('psalm-closure-this-type', null);
            }

            if ($function_params === null) {
                if (self::evaluateArbitraryParam(
                    $statements_analyzer,
                    $arg,
                    $context,
                ) === false) {
                    return false;
                }

                continue;
            }

            $param = null;

            if ($arg->name && $allow_named_args) {
                foreach ($function_params as $candidate_param) {
                    if ($candidate_param->name === $arg->name->name) {
                        $param = $candidate_param;
                        break;
                    }
                }

                if ($param === null && $last_param && $last_param->is_variadic) {
                    $param = $last_param;
                }
            } elseif ($argument_offset < count($function_params)) {
                $param = $function_params[$argument_offset];
            } elseif ($last_param && $last_param->is_variadic) {
                $param = $last_param;
            }

            $by_ref = $param && $param->by_ref;

            $by_ref_type = null;

            if ($by_ref) {
                $by_ref_type = $param->type ?: Type::getMixed();
            }

            if ($by_ref
                && $by_ref_type
                && !($arg->value instanceof PhpParser\Node\Expr\Closure
                    || $arg->value instanceof PhpParser\Node\Expr\ConstFetch
                    || $arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
                    || $arg->value instanceof PhpParser\Node\Expr\FuncCall
                    || $arg->value instanceof PhpParser\Node\Expr\MethodCall
                    || $arg->value instanceof PhpParser\Node\Expr\StaticCall
                    || $arg->value instanceof PhpParser\Node\Expr\New_
                    || $arg->value instanceof PhpParser\Node\Expr\Assign
                    || $arg->value instanceof PhpParser\Node\Expr\Array_
                    || $arg->value instanceof PhpParser\Node\Expr\Ternary
                    || $arg->value instanceof PhpParser\Node\Expr\BinaryOp
                )
            ) {
                if (self::handleByRefFunctionArg(
                    $statements_analyzer,
                    $method_id,
                    $argument_offset,
                    $arg,
                    $context,
                ) === false) {
                    return false;
                }

                continue;
            }

            $toggled_class_exists = false;

            if (in_array($method_id, ['class_exists', 'interface_exists', 'enum_exists', 'trait_exists'], true)
                && $argument_offset === 0
                && !$context->inside_class_exists
            ) {
                $context->inside_class_exists = true;
                $toggled_class_exists = true;
            }

            $high_order_template_result = null;
            $high_order_callable_info = $param
                ? HighOrderFunctionArgHandler::getCallableArgInfo($context, $arg->value, $statements_analyzer, $param)
                : null;

            if ($param && $high_order_callable_info) {
                $high_order_template_result = HighOrderFunctionArgHandler::remapLowerBounds(
                    $statements_analyzer,
                    $template_result ?? new TemplateResult([], []),
                    $high_order_callable_info,
                    $param->type ?? Type::getMixed(),
                );
            } elseif (($arg->value instanceof PhpParser\Node\Expr\Closure
                    || $arg->value instanceof PhpParser\Node\Expr\ArrowFunction)
                && $param
                && !$arg->value->getDocComment()
            ) {
                self::handleClosureArg(
                    $statements_analyzer,
                    $args,
                    $method_id,
                    $context,
                    $template_result ?? new TemplateResult([], []),
                    $argument_offset,
                    $arg,
                    $param,
                );
            }

            if ($arg->value instanceof PhpParser\Node\Expr\Closure
                || $arg->value instanceof PhpParser\Node\Expr\ArrowFunction
            ) {
                if ($param && $param->closure_this_type) {
                    self::applyParamClosureThisHint(
                        $statements_analyzer,
                        $method_id,
                        $context,
                        $template_result ?? new TemplateResult([], []),
                        $arg,
                        $param,
                    );
                }
            }

            $was_inside_call = $context->inside_call;
            $context->inside_call = true;

            $was_inside_isset = $context->inside_isset;
            $context->inside_isset = false;

            if (ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $arg->value,
                $context,
                false,
                null,
                null,
                $high_order_template_result,
            ) === false) {
                $context->inside_isset = $was_inside_isset;
                $context->inside_call = $was_inside_call;

                return false;
            }

            $context->inside_isset = $was_inside_isset;
            $context->inside_call = $was_inside_call;

            if ($high_order_callable_info && $high_order_template_result) {
                HighOrderFunctionArgHandler::enhanceCallableArgType(
                    $context,
                    $arg->value,
                    $statements_analyzer,
                    $high_order_callable_info,
                    $high_order_template_result,
                );
            }

            if (($argument_offset === 0 && in_array($method_id, self::ARRAY_FILTERLIKE, true) && count($args) === 2)
                || ($argument_offset > 0 && $method_id === 'array_map' && count($args) >= 2)
            ) {
                self::handleArrayMapFilterArrayArg(
                    $statements_analyzer,
                    $method_id,
                    $argument_offset,
                    $arg,
                    $context,
                    $template_result,
                );
            }

            $inferred_arg_type = $statements_analyzer->node_data->getType($arg->value);

            if (null !== $inferred_arg_type
                && null !== $template_result
                && null !== $param
                && null !== $param->type
                && !$arg->unpack
            ) {
                $codebase = $statements_analyzer->getCodebase();

                TemplateStandinTypeReplacer::fillTemplateResult(
                    $param->type,
                    $template_result,
                    $codebase,
                    $statements_analyzer,
                    $inferred_arg_type,
                    $argument_offset,
                    $context->self,
                    $context->calling_method_id ?: $context->calling_function_id,
                );
            }

            if ($toggled_class_exists) {
                $context->inside_class_exists = false;
            }
        }

        if ($method_id === "ReflectionClass::getattributes"
            || $method_id === "ReflectionClassConstant::getattributes"
            || $method_id === "ReflectionFunction::getattributes"
            || $method_id === "ReflectionMethod::getattributes"
            || $method_id === "ReflectionParameter::getattributes"
            || $method_id === "ReflectionProperty::getattributes"
        ) {
            AttributesAnalyzer::analyzeGetAttributes($statements_analyzer, $method_id, array_values($args));
        }

        return null;
    }

    private static function handleArrayMapFilterArrayArg(
        StatementsAnalyzer $statements_analyzer,
        string $method_id,
        int $argument_offset,
        PhpParser\Node\Arg $arg,
        Context $context,
        ?TemplateResult &$template_result,
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        $template_types = ['ArrayValue' . $argument_offset => [$method_id => Type::getMixed()]];

        $replace_template_result = new TemplateResult(
            $template_types,
            [],
        );

        $existing_type = $statements_analyzer->node_data->getType($arg->value);

        TemplateStandinTypeReplacer::fillTemplateResult(
            new Union([
                new TArray([
                    Type::getArrayKey(),
                    new Union([
                        new TTemplateParam(
                            'ArrayValue' . $argument_offset,
                            Type::getMixed(),
                            $method_id,
                        ),
                    ]),
                ]),
            ]),
            $replace_template_result,
            $codebase,
            $statements_analyzer,
            $existing_type,
            $argument_offset,
            $context->self,
            $context->calling_method_id ?: $context->calling_function_id,
        );

        if ($replace_template_result->lower_bounds) {
            if (!$template_result) {
                $template_result = new TemplateResult([], []);
            }

            $template_result->lower_bounds += $replace_template_result->lower_bounds;
        }
    }

    /**
     * @param   array<int, PhpParser\Node\Arg>  $args
     */
    private static function handleClosureArg(
        StatementsAnalyzer $statements_analyzer,
        array $args,
        ?string $method_id,
        Context $context,
        TemplateResult $template_result,
        int $argument_offset,
        PhpParser\Node\Arg $arg,
        FunctionLikeParameter $param,
    ): void {
        if (!$param->type) {
            return;
        }

        $codebase = $statements_analyzer->getCodebase();

        if (($argument_offset === 1 && in_array($method_id, self::ARRAY_FILTERLIKE, true) && count($args) === 2)
            || ($argument_offset === 0 && $method_id === 'array_map' && count($args) >= 2)
        ) {
            $function_like_params = [];

            foreach ($template_result->lower_bounds as $template_name => $_) {
                $t = new Union([
                    new TTemplateParam(
                        $template_name,
                        Type::getMixed(),
                        $method_id,
                    ),
                ]);
                $function_like_params[] = new FunctionLikeParameter(
                    'function',
                    false,
                    $t,
                    $t,
                );
            }

            $replaced_type = new Union([
                new TCallable(
                    array_reverse($function_like_params),
                ),
            ]);
        } else {
            $replaced_type = $param->type;
        }

        $new_bounds = $template_result->template_types;
        foreach ($template_result->lower_bounds as $k => $template_map) {
            $new_bounds[$k] = [];
            foreach ($template_map as $kk => $lower_bounds) {
                $new_bounds[$k][$kk] = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                    $lower_bounds,
                    $codebase,
                );
            }
        }
        $replace_template_result = new TemplateResult(
            $new_bounds,
            [],
        );
        unset($new_bounds);

        $replaced_type = TemplateStandinTypeReplacer::replace(
            $replaced_type,
            $replace_template_result,
            $codebase,
            $statements_analyzer,
            null,
            null,
            null,
            $context->calling_method_id ?: $context->calling_function_id,
        );

        $replaced_type = TemplateInferredTypeReplacer::replace(
            $replaced_type,
            $replace_template_result,
            $codebase,
        );

        $closure_id = strtolower($statements_analyzer->getFilePath())
            . ':' . $arg->value->getLine()
            . ':' . (int)$arg->value->getAttribute('startFilePos')
            . ':-:closure';

        try {
            $closure_storage = $codebase->getClosureStorage(
                $statements_analyzer->getFilePath(),
                $closure_id,
            );
        } catch (UnexpectedValueException) {
            return;
        }

        foreach ($closure_storage->params as $closure_param_offset => $param_storage) {
            $param_type_inferred = $param_storage->type_inferred;

            $newly_inferred_type = null;
            $has_different_docblock_type = false;

            if ($param_storage->type && !$param_type_inferred) {
                if ($param_storage->type !== $param_storage->signature_type) {
                    $has_different_docblock_type = true;
                }
            }

            if (!$has_different_docblock_type) {
                foreach ($replaced_type->getAtomicTypes() as $replaced_type_part) {
                    if ($replaced_type_part instanceof TCallable
                        || $replaced_type_part instanceof TClosure
                    ) {
                        if (isset($replaced_type_part->params[$closure_param_offset]->type)) {
                            $replaced_param_type = $replaced_type_part->params[$closure_param_offset]->type;

                            $type_variable_resolver = new TypeVariableResolver($codebase);
                            $type_variable_resolver->traverse($replaced_param_type);

                            if ($replaced_param_type->hasTemplate()) {
                                $replaced_param_type = TypeExpander::expandUnion(
                                    $codebase,
                                    $replaced_param_type,
                                    null,
                                    null,
                                    null,
                                    true,
                                    false,
                                    false,
                                    true,
                                    true,
                                );
                            }

                            if ($param_storage->type && !$param_type_inferred) {
                                $param_comparison_result = new TypeComparisonResult();

                                $type_match_found = UnionTypeComparator::isContainedBy(
                                    $codebase,
                                    $replaced_param_type,
                                    $param_storage->type,
                                    false,
                                    false,
                                    $param_comparison_result,
                                );

                                if (!$type_match_found) {
                                    continue;
                                }

                                if ($param_comparison_result->type_variable_lower_bounds
                                    || $param_comparison_result->type_variable_upper_bounds
                                ) {
                                    // a containment that recorded type-variable bounds is
                                    // provisional, not definitive: keep the declared type
                                    continue;
                                }
                            }

                            $newly_inferred_type = Type::combineUnionTypes(
                                $newly_inferred_type,
                                $replaced_param_type,
                                $codebase,
                            );
                        }
                    }
                }
            }

            if ($newly_inferred_type) {
                $param_storage->type = $newly_inferred_type;
                $param_storage->type_inferred = true;
            }

            if ($method_id === 'array_map' || in_array($method_id, self::ARRAY_FILTERLIKE, true)) {
                self::taintClosureParamWithArrayElements(
                    $statements_analyzer,
                    $args[1 - $argument_offset]->value,
                    $param_storage,
                    $method_id === 'array_map'
                        ? ArrayMapReturnTypeProvider::getElementMarker($statements_analyzer, $args)
                        : null,
                );
            }
        }
    }

    /**
     * The parameter of the closure array_map() or a function like array_filter() is given takes the elements of the
     * array: through its type, or, if it has none to hold them, through the node the closure assigns it from. With
     * $element_marker, each with its own key (see ArrayMapReturnTypeProvider::getElementMarker()).
     */
    private static function taintClosureParamWithArrayElements(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $array,
        FunctionLikeParameter $param_storage,
        ?string $element_marker,
    ): void {
        $temp = Type::getMixed();

        if ($param_storage->type) {
            ArrayFetchAnalyzer::taintArrayFetch(
                $statements_analyzer,
                $array,
                null,
                $param_storage->type,
                $temp,
                foreach_marker: $element_marker,
            );

            return;
        }

        if (!$param_storage->location || !$graph = $statements_analyzer->getDataFlowGraphWithSuppressed()) {
            return;
        }

        $element_type = Type::getMixed();
        ArrayFetchAnalyzer::taintArrayFetch(
            $statements_analyzer,
            $array,
            null,
            $element_type,
            $temp,
            foreach_marker: $element_marker,
        );

        // see FunctionLikeAnalyzer::processParams()
        $param_node = DataFlowNode::getForAssignment('$' . $param_storage->name, $param_storage->location);
        $graph->addNode($param_node);

        foreach ($element_type->parent_nodes as $parent_node) {
            $graph->addPath($parent_node, $param_node, '=');
        }
    }

    /**
     * Resolves `@param-closure-this` against the call site and stamps the resolved type as
     * a PHP-Parser node attribute on the Closure/ArrowFunction so ClosureAnalyzer can bind
     * `$this` inside the closure body.
     */
    private static function applyParamClosureThisHint(
        StatementsAnalyzer $statements_analyzer,
        ?string $method_id,
        Context $context,
        TemplateResult $template_result,
        PhpParser\Node\Arg $arg,
        FunctionLikeParameter $param,
    ): void {
        if (!$param->closure_this_type) {
            return;
        }

        $codebase = $statements_analyzer->getCodebase();

        $self_fq_class_name = $context->self;
        $static_fq_class_name = null;
        $parent_fq_class_name = null;
        $static_class_is_final = false;

        if ($method_id !== null && MethodIdentifier::isValidMethodIdReference($method_id)) {
            $called_method_id = MethodIdentifier::fromMethodIdReference($method_id);
            $called_class = $called_method_id->fq_class_name;

            $static_fq_class_name = $called_class;
            $self_fq_class_name = $called_class;

            if ($codebase->classlike_storage_provider->has($called_class)) {
                $called_class_storage = $codebase->classlike_storage_provider->get($called_class);
                $static_class_is_final = $called_class_storage->final;

                // `self` is the class where the method appears. For a trait, that is
                // its consuming class; for normal inheritance, it is the declaring class.
                $declaring_method_id = $codebase->methods->getDeclaringMethodId($called_method_id);

                if ($declaring_method_id !== null) {
                    $self_fq_class_name = $declaring_method_id->fq_class_name;

                    $appearing_method_id = $codebase->methods->getAppearingMethodId($called_method_id);

                    if ($appearing_method_id !== null && $declaring_method_id !== $appearing_method_id) {
                        $self_fq_class_name = $appearing_method_id->fq_class_name;
                    }
                }
            }
        }

        if ($self_fq_class_name !== null
            && $codebase->classlike_storage_provider->has($self_fq_class_name)
        ) {
            $parent_fq_class_name = $codebase->classlike_storage_provider->get($self_fq_class_name)
                ->parent_class;
        }

        $closure_this_type = $param->closure_this_type;

        if ($template_result->lower_bounds || $template_result->template_types) {
            $closure_this_type = TemplateStandinTypeReplacer::replace(
                $closure_this_type,
                $template_result,
                $codebase,
                $statements_analyzer,
                null,
                null,
                $context->self,
                $context->calling_method_id ?? $context->calling_function_id,
            );

            $closure_this_type = TemplateInferredTypeReplacer::replace(
                $closure_this_type,
                $template_result,
                $codebase,
            );
        }

        $static_type = $static_fq_class_name !== null
            ? new TNamedObject($static_fq_class_name, true, $static_class_is_final)
            : null;

        $closure_this_type = TypeExpander::expandUnion(
            $codebase,
            $closure_this_type,
            $self_fq_class_name,
            $static_type,
            $parent_fq_class_name,
            true,
            false,
            $static_class_is_final,
            true,
        );

        $arg->value->setAttribute('psalm-closure-this-type', $closure_this_type);
    }

    /**
     * @param   list<PhpParser\Node\Arg>  $args
     * @param   array<int,FunctionLikeParameter>        $function_params
     * @return  false|null
     * @psalm-suppress ComplexMethod there's just not much that can be done about this
     */
    public static function checkArgumentsMatch(
        StatementsAnalyzer $statements_analyzer,
        array $args,
        string|MethodIdentifier|null $method_id,
        array $function_params,
        ?FunctionLikeStorage $function_storage,
        ?ClassLikeStorage $class_storage,
        TemplateResult $template_result,
        CodeLocation $code_location,
        Context $context,
    ): ?bool {
        $in_call_map = $method_id ? InternalCallMapHandler::inCallMap((string) $method_id) : false;

        $cased_method_id = (string) $method_id;

        $is_variadic = false;

        $fq_class_name = null;

        $codebase = $statements_analyzer->getCodebase();

        $specialize_taint = !$function_storage || TaintFlowGraph::isCallSpecialized(
            $statements_analyzer->getTaintFlowGraphWithSuppressed(),
            $codebase,
            $function_storage,
            $code_location,
        );

        if ($method_id) {
            if ($method_id instanceof MethodIdentifier) {
                $fq_class_name = $method_id->fq_class_name;
            }

            if ($function_storage) {
                $is_variadic = $function_storage->variadic;
            } elseif (is_string($method_id)) {
                $is_variadic = Functions::isVariadic(
                    $codebase,
                    strtolower($method_id),
                    $statements_analyzer->getRootFilePath(),
                );
            } else {
                $is_variadic = $codebase->methods->isVariadic($method_id);
            }
        }

        if ($method_id instanceof MethodIdentifier) {
            $cased_method_id = $codebase->methods->getCasedMethodId($method_id);
        } elseif ($function_storage) {
            $cased_method_id = $function_storage->cased_name;
        }

        $calling_class_storage = $class_storage;

        $static_fq_class_name = $fq_class_name;
        $self_fq_class_name = $fq_class_name;

        if ($method_id instanceof MethodIdentifier) {
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            if ($declaring_method_id && (string)$declaring_method_id !== (string)$method_id) {
                $self_fq_class_name = $declaring_method_id->fq_class_name;
                $class_storage = $codebase->classlike_storage_provider->get($self_fq_class_name);
            }

            $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);

            if ($appearing_method_id && $declaring_method_id !== $appearing_method_id) {
                $self_fq_class_name = $appearing_method_id->fq_class_name;
            }
        }

        if ($function_params && !$is_variadic) {
            foreach ($function_params as $function_param) {
                $is_variadic = $is_variadic || $function_param->is_variadic;
            }
        }

        $has_packed_var = false;

        foreach ($args as $arg) {
            if ($arg->unpack) {
                $has_packed_var = true;
            }
        }

        $last_param = $function_params
            ? $function_params[count($function_params) - 1]
            : null;

        $class_generic_params = [];

        foreach ($template_result->lower_bounds as $template_name => $type_map) {
            foreach ($type_map as $class => $lower_bounds) {
                if (count($lower_bounds) === 1) {
                    $class_generic_params[$template_name][$class] = reset($lower_bounds)->type;
                }
            }
        }

        if ($function_storage) {
            $template_result = self::getProvisionalTemplateResultForFunctionLike(
                $statements_analyzer,
                $codebase,
                $context,
                $class_storage,
                $self_fq_class_name,
                $calling_class_storage,
                $function_storage,
                $class_generic_params,
                $template_result,
                $args,
                $function_params,
                $last_param,
            );
        }

        $function_param_count = count($function_params);

        if (count($function_params) > count($args) && !$has_packed_var) {
            for ($i = count($args), $iMax = count($function_params); $i < $iMax; $i++) {
                if ($function_params[$i]->default_type
                    && $function_params[$i]->type
                    && $function_params[$i]->type->hasTemplate()
                ) {
                    if ($function_params[$i]->default_type instanceof Union) {
                        $default_type = $function_params[$i]->default_type;
                    } else {
                        $default_type_atomic = ConstantTypeResolver::resolve(
                            $codebase->classlikes,
                            $function_params[$i]->default_type,
                            $statements_analyzer,
                        );

                        $default_type = new Union([$default_type_atomic]);
                    }

                    if ($default_type->hasLiteralValue()) {
                        ArgumentAnalyzer::checkArgumentMatches(
                            $statements_analyzer,
                            $cased_method_id,
                            $method_id instanceof MethodIdentifier ? $method_id : null,
                            $self_fq_class_name,
                            $static_fq_class_name,
                            $code_location,
                            $function_storage,
                            $function_params[$i],
                            $i,
                            $i,
                            $function_storage->allow_named_arg_calls ?? true,
                            new VirtualArg(
                                StubsGenerator::getExpressionFromType($default_type),
                            ),
                            $default_type,
                            $context,
                            $class_generic_params,
                            $template_result,
                            $specialize_taint,
                            $in_call_map,
                        );
                    }
                }
            }
        }

        if (($method_id === 'preg_match_all' || $method_id === 'preg_match') && count($args) > 3) {
            $args = array_reverse($args, true);
        }

        $arg_function_params = [];
        $matched_args = [];
        $named_args_was_used = false;

        foreach ($args as $argument_offset => $arg) {
            if ($named_args_was_used && !$arg->name) {
                IssueBuffer::maybeAdd(
                    new InvalidNamedArgument(
                        'Cannot use positional argument after named argument',
                        new CodeLocation($statements_analyzer, $arg),
                        (string)$method_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            if ($arg->unpack) {
                if ($function_param_count > $argument_offset) {
                    for ($i = $argument_offset; $i < $function_param_count; $i++) {
                        $arg_function_params[$argument_offset][] = $function_params[$i];
                    }
                }

                if (($arg_value_type = $statements_analyzer->node_data->getType($arg->value))
                    && $arg_value_type->hasArray()) {
                    /**
                     * @var TArray|TKeyedArray
                     */
                    $array_type = $arg_value_type->getArray();

                    if ($array_type instanceof TKeyedArray) {
                        $array_type = $array_type->getGenericArrayType();
                        $key_types = $array_type->type_params[0]->getAtomicTypes();

                        foreach ($key_types as $key_type) {
                            if (!$key_type instanceof TLiteralString
                                || ($function_storage && !$function_storage->allow_named_arg_calls)) {
                                continue;
                            }

                            $param_found = false;

                            foreach ($function_params as $candidate_param) {
                                if ($candidate_param->name === $key_type->value || $candidate_param->is_variadic) {
                                    if ($candidate_param->name === $key_type->value) {
                                        if (isset($matched_args[$candidate_param->name])) {
                                            IssueBuffer::maybeAdd(
                                                new InvalidNamedArgument(
                                                    'Parameter $' . $key_type->value . ' has already been used in '
                                                    . ($cased_method_id ?: $method_id),
                                                    new CodeLocation($statements_analyzer, $arg),
                                                    (string)$method_id,
                                                ),
                                                $statements_analyzer->getSuppressedIssues(),
                                            );
                                        }

                                        $matched_args[$candidate_param->name] = true;
                                    }

                                    $param_found = true;
                                    break;
                                }
                            }

                            if (!$param_found) {
                                IssueBuffer::maybeAdd(
                                    new InvalidNamedArgument(
                                        'Parameter $' . $key_type->value . ' does not exist on function '
                                            . ($cased_method_id ?: $method_id),
                                        new CodeLocation($statements_analyzer, $arg),
                                        (string)$method_id,
                                    ),
                                    $statements_analyzer->getSuppressedIssues(),
                                );
                            }
                        }
                    }
                }
            } elseif ($arg->name && (!$function_storage || $function_storage->allow_named_arg_calls)) {
                $named_args_was_used = true;

                foreach ($function_params as $candidate_param) {
                    if ($candidate_param->name === $arg->name->name || $candidate_param->is_variadic) {
                        if ($candidate_param->name === $arg->name->name) {
                            if (isset($matched_args[$candidate_param->name])) {
                                IssueBuffer::maybeAdd(
                                    new InvalidNamedArgument(
                                        'Parameter $' . $arg->name->name . ' has already been used in '
                                            . ($cased_method_id ?: $method_id),
                                        new CodeLocation($statements_analyzer, $arg->name),
                                        (string) $method_id,
                                    ),
                                    $statements_analyzer->getSuppressedIssues(),
                                );
                            }

                            $matched_args[$candidate_param->name] = true;
                        }

                        $arg_function_params[$argument_offset] = [$candidate_param];
                        break;
                    }
                }

                if (!isset($arg_function_params[$argument_offset])) {
                    IssueBuffer::maybeAdd(
                        new InvalidNamedArgument(
                            'Parameter $' . $arg->name->name . ' does not exist on function '
                            . ($cased_method_id ?: $method_id),
                            new CodeLocation($statements_analyzer, $arg->name),
                            (string) $method_id,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            } elseif ($function_param_count > $argument_offset) {
                $arg_function_params[$argument_offset] = [$function_params[$argument_offset]];
                $matched_args[$function_params[$argument_offset]->name] = true;
            } elseif ($last_param && $last_param->is_variadic) {
                $arg_function_params[$argument_offset] = [$last_param];
                $matched_args[$last_param->name] = true;
            }
        }

        foreach ($args as $argument_offset => $arg) {
            if (!isset($arg_function_params[$argument_offset])) {
                continue;
            }

            if ($arg_function_params[$argument_offset][0]->by_ref
                && $method_id !== 'extract'
            ) {
                if (self::handlePossiblyMatchingByRefParam(
                    $statements_analyzer,
                    $codebase,
                    (string) $method_id,
                    $cased_method_id,
                    $last_param,
                    $function_params,
                    $argument_offset,
                    $arg,
                    $context,
                    $template_result,
                    $method_id instanceof MethodIdentifier ? $method_id : null,
                    $in_call_map ? null : $function_storage,
                    $code_location,
                    $args,
                ) === false) {
                    return null;
                }
            }

            $arg_value_type = $statements_analyzer->node_data->getType($arg->value);

            foreach ($arg_function_params[$argument_offset] as $i => $function_param) {
                if (ArgumentAnalyzer::checkArgumentMatches(
                    $statements_analyzer,
                    $cased_method_id,
                    $method_id instanceof MethodIdentifier ? $method_id : null,
                    $self_fq_class_name,
                    $static_fq_class_name,
                    $code_location,
                    $function_storage,
                    $function_param,
                    $argument_offset + $i,
                    $i,
                    $function_storage->allow_named_arg_calls ?? true,
                    $arg,
                    $arg_value_type,
                    $context,
                    $class_generic_params,
                    $template_result,
                    $specialize_taint,
                    $in_call_map,
                ) === false) {
                    return false;
                }
            }
        }

        if ($statements_analyzer->taint_flow_graph
            && $cased_method_id
            && !self::returnsInsteadOfOutputting($statements_analyzer, $cased_method_id, $args)
        ) {
            foreach ($args as $argument_offset => $_) {
                if (!isset($arg_function_params[$argument_offset])) {
                    continue;
                }

                if ($in_call_map && $argument_offset === 1 && strtolower($cased_method_id) === 'curl_setopt_array') {
                    self::addCurlOptionArraySinks(
                        $statements_analyzer->taint_flow_graph,
                        $codebase,
                        $cased_method_id,
                        $code_location,
                    );

                    continue;
                }

                foreach ($arg_function_params[$argument_offset] as $function_param) {
                    $sinks = self::getArgumentSinks($cased_method_id, $argument_offset, $args, $function_param->sinks);

                    if ($sinks) {
                        if (!$function_storage) {
                            // Mirror the value-node keying in ArgumentAnalyzer::processTaintedness:
                            // when the caller has no storage, resolve it from the cased method id so
                            // the sink is keyed by the declared parameter index (via $function_param),
                            // and only a genuinely storage-less callable falls back to the call offset.
                            $sink = ($in_call_map
                                ? null
                                : DataFlowNode::getForMethodArgumentById(
                                    $codebase->methods,
                                    $cased_method_id,
                                    $argument_offset,
                                    $code_location,
                                    $function_param,
                                ))
                                ?? DataFlowNode::getForCallableArg(
                                    $in_call_map
                                        ? 'builtin'
                                        : ($method_id instanceof MethodIdentifier
                                            ? 'magic-method'
                                            : 'callable-object'),
                                    $cased_method_id,
                                    $argument_offset,
                                    $code_location,
                                    $sinks,
                                );
                        } elseif ($specialize_taint) {
                            $sink = DataFlowNode::getForMethodArgument(
                                $cased_method_id,
                                DataFlowNode::getParameterOffset(
                                    $function_storage,
                                    $function_param,
                                    $argument_offset,
                                ),
                                $function_storage,
                                $code_location,
                            );
                        } else {
                            $sink = DataFlowNode::getForMethodArgument(
                                $cased_method_id,
                                DataFlowNode::getParameterOffset(
                                    $function_storage,
                                    $function_param,
                                    $argument_offset,
                                ),
                                $function_storage,
                                null,
                            );
                        }

                        $statements_analyzer->taint_flow_graph->addSink($sink);
                    }
                }
            }
        }

        $f = in_array($method_id, self::ARRAY_FILTERLIKE, true);
        if ($f || $method_id === 'array_map') {
            assert(is_string($method_id));
            if (!$f && count($args) < 2) {
                IssueBuffer::maybeAdd(
                    new TooFewArguments(
                        'Too few arguments for ' . $method_id,
                        $code_location,
                        $method_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            } elseif ($f && count($args) < 1) {
                IssueBuffer::maybeAdd(
                    new TooFewArguments(
                        'Too few arguments for ' . $method_id,
                        $code_location,
                        $method_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            ArrayFunctionArgumentsAnalyzer::checkArgumentsMatch(
                $statements_analyzer,
                $context,
                $args,
                $method_id,
                $context->check_functions,
            );

            return null;
        }

        if ($method_id === 'get_class' && $args === []) {
            //get_class without args only works when inside a class
            if (!$context->self) {
                IssueBuffer::maybeAdd(
                    new TooFewArguments(
                        'Cannot call get_class() without argument outside of class scope',
                        $code_location,
                        $method_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );

                return null;
            }
        }

        self::checkArgCount(
            $statements_analyzer,
            $codebase,
            $function_storage,
            $context,
            $template_result,
            $is_variadic,
            $args,
            $function_params,
            $in_call_map,
            $method_id,
            $cased_method_id,
            $code_location,
        );

        return null;
    }

    /**
     * @param  array<int, FunctionLikeParameter> $function_params
     * @param  array<int, PhpParser\Node\Arg> $args
     * @return false|null
     */
    private static function handlePossiblyMatchingByRefParam(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        string $method_id,
        ?string $cased_method_id,
        ?FunctionLikeParameter $last_param,
        array $function_params,
        int $argument_offset,
        PhpParser\Node\Arg $arg,
        Context $context,
        ?TemplateResult $template_result,
        ?MethodIdentifier $method_identifier,
        ?FunctionLikeStorage $function_storage,
        CodeLocation $call_location,
        array $args,
    ): ?bool {
        if ($arg->value instanceof PhpParser\Node\Scalar
            || $arg->value instanceof PhpParser\Node\Expr\Cast
            || $arg->value instanceof PhpParser\Node\Expr\Array_
            || $arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
            || $arg->value instanceof PhpParser\Node\Expr\BinaryOp
            || $arg->value instanceof PhpParser\Node\Expr\Ternary
            || (
                (
                $arg->value instanceof PhpParser\Node\Expr\ConstFetch
                    || $arg->value instanceof PhpParser\Node\Expr\FuncCall
                    || $arg->value instanceof PhpParser\Node\Expr\MethodCall
                    || $arg->value instanceof PhpParser\Node\Expr\StaticCall
                ) && (
                    !($arg_value_type = $statements_analyzer->node_data->getType($arg->value))
                    || !$arg_value_type->by_ref
                )
            )
        ) {
            IssueBuffer::maybeAdd(
                new InvalidPassByReference(
                    'Parameter ' . ($argument_offset + 1) . ' of ' . $cased_method_id . ' expects a variable',
                    new CodeLocation($statements_analyzer->getSource(), $arg->value),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );

            return false;
        }

        if (!in_array(
            $method_id,
            [
                'ksort', 'asort', 'krsort', 'arsort', 'natcasesort', 'natsort',
                'reset', 'end', 'next', 'prev', 'array_pop', 'array_shift',
                'array_push', 'array_unshift', 'socket_select', 'array_splice',
            ],
            true,
        )) {
            $by_ref_type = null;
            $by_ref_out_type = null;
            $source_param = null;
            $function_param = null;

            $check_null_ref = true;

            if ($last_param) {
                if ($arg->name !== null) {
                    $function_param = array_reduce(
                        $function_params,
                        static function (
                            ?FunctionLikeParameter $function_param,
                            FunctionLikeParameter $param,
                        ) use (
                            $arg,
                        ) {
                            if ($param->name === $arg->name->name) {
                                return $param;
                            }
                            return $function_param;
                        },
                        null,
                    );
                    if ($function_param === null) {
                        return false;
                    }
                } elseif ($argument_offset < count($function_params)) {
                    $function_param = $function_params[$argument_offset];
                } else {
                    $function_param = $last_param;
                }

                if ($function_param->type) {
                    $by_ref_type = $function_param->type;
                }
                if ($function_param->out_type) {
                    $by_ref_out_type = $function_param->out_type;
                }

                if (!str_contains($method_id, '::')) {
                    $source_param = $function_param->name;
                }

                if ($by_ref_type && $by_ref_type->isNullable()) {
                    $check_null_ref = false;
                }

                if ($template_result && $by_ref_type) {
                    $original_by_ref_type = $by_ref_type;

                    $by_ref_type = TemplateStandinTypeReplacer::replace(
                        $by_ref_type,
                        $template_result,
                        $codebase,
                        $statements_analyzer,
                        $statements_analyzer->node_data->getType($arg->value),
                        $argument_offset,
                        $context->self,
                        $context->calling_method_id ?: $context->calling_function_id,
                    );

                    if ($template_result->lower_bounds) {
                        $original_by_ref_type = TemplateInferredTypeReplacer::replace(
                            $original_by_ref_type,
                            $template_result,
                            $codebase,
                        );

                        $by_ref_type = $original_by_ref_type;
                    }
                }

                if ($template_result && $by_ref_out_type) {
                    $original_by_ref_out_type = $by_ref_out_type;

                    $by_ref_out_type = TemplateStandinTypeReplacer::replace(
                        $by_ref_out_type,
                        $template_result,
                        $codebase,
                        $statements_analyzer,
                        $statements_analyzer->node_data->getType($arg->value),
                        $argument_offset,
                        $context->self,
                        $context->calling_method_id ?: $context->calling_function_id,
                    );

                    if ($template_result->lower_bounds) {
                        $original_by_ref_out_type = TemplateInferredTypeReplacer::replace(
                            $original_by_ref_out_type,
                            $template_result,
                            $codebase,
                        );

                        $by_ref_out_type = $original_by_ref_out_type;
                    }
                }

                if ($by_ref_type && $function_param->is_variadic && $arg->unpack) {
                    $by_ref_type = new Union([
                        new TArray([
                            Type::getInt(),
                            $by_ref_type,
                        ]),
                    ]);
                }
            }

            $by_ref_type = $by_ref_type ?: Type::getMixed();
            $by_ref_out_type = $by_ref_out_type ?: $by_ref_type;

            // what the function-like leaves in the parameter (see FunctionLikeAnalyzer::taintByRefParamsOut())
            $out_type_holds_value = false;
            if ($function_storage !== null
                && $function_param !== null
                && ($graph = $statements_analyzer->getTaintFlowGraphWithSuppressed())
            ) {
                $out_node = self::getByRefParamOutNode(
                    $codebase,
                    $cased_method_id ?? $method_id,
                    $method_identifier,
                    $function_storage,
                    $function_param,
                    $argument_offset,
                    $call_location,
                );

                $graph->addNode($out_node);

                // the value passed may still be there after a call of a function-like whose body isn't
                // analyzed: one without a body, or out of the project files
                $out_type_holds_value = self::hasAnalyzedBody($codebase, $method_identifier, $function_storage);

                $by_ref_out_type = $out_type_holds_value
                    ? $by_ref_out_type->setParentNodes([$out_node->id => $out_node])
                    : $by_ref_out_type->addParentNodes([$out_node->id => $out_node]);
            }

            // a builtin filling this parameter with data given to other ones (preg_match(), ...)
            if ($function_storage === null
                && $function_param !== null
                && $statements_analyzer->getTaintFlowGraphWithSuppressed()
            ) {
                foreach (self::getByRefFlowInputs($method_id, $function_param->name) as $input_name) {
                    $input_arg = self::getArgForParam($args, $function_params, $input_name);
                    $input_type = $input_arg ? $statements_analyzer->node_data->getType($input_arg->value) : null;

                    if ($input_type && $input_type->parent_nodes) {
                        $by_ref_out_type = $by_ref_out_type->addParentNodes($input_type->parent_nodes);
                    }
                }
            }

            // a builtin reading from outside the program into this parameter (socket_recv(), ...)
            if ($source_param !== null
                && ($graph = $statements_analyzer->getTaintFlowGraphWithSuppressed())
                && ($source_taints = InternalTaintSourceMap::getTaints($method_id, $source_param)) !== 0
            ) {
                $by_ref_out_type = InternalTaintSourceMap::addSource(
                    $graph,
                    $statements_analyzer,
                    $arg->value,
                    $method_id . '($' . $source_param . ')',
                    $source_taints,
                    $context,
                    $by_ref_out_type,
                );
            }

            AssignmentAnalyzer::assignByRefParam(
                $statements_analyzer,
                $arg->value,
                $by_ref_type,
                $by_ref_out_type,
                $context,
                $method_id && (str_contains($method_id, '::') || !InternalCallMapHandler::inCallMap($method_id)),
                $check_null_ref,
                $out_type_holds_value,
            );
        }

        return null;
    }

    /**
     * The parameters of a builtin whose taints flow into its by-reference parameter $param_name
     * (see dictionaries/InternalTaintByRefFlowMap.php)
     *
     * @return list<string>
     * @psalm-capabilities read-globals|write-globals
     */
    private static function getByRefFlowInputs(string $function_id, string $param_name): array
    {
        if (self::$by_ref_flow_map === null) {
            /** @var array<lowercase-string, array<string, list<string>>> */
            self::$by_ref_flow_map = require(dirname(__DIR__, 7) . '/dictionaries/InternalTaintByRefFlowMap.php');
        }

        return self::$by_ref_flow_map[strtolower($function_id)][$param_name] ?? [];
    }

    /**
     * @param array<int, PhpParser\Node\Arg> $args
     * @param array<int, FunctionLikeParameter> $function_params
     * @psalm-mutation-free
     */
    private static function getArgForParam(array $args, array $function_params, string $param_name): ?PhpParser\Node\Arg
    {
        foreach ($args as $arg) {
            if ($arg->name !== null && $arg->name->name === $param_name) {
                return $arg;
            }
        }

        foreach ($function_params as $offset => $param) {
            if ($param->name === $param_name) {
                return isset($args[$offset]) && $args[$offset]->name === null ? $args[$offset] : null;
            }
        }

        return null;
    }

    /**
     * @psalm-capabilities read-props
     */
    private static function hasAnalyzedBody(
        Codebase $codebase,
        ?MethodIdentifier $method_id,
        FunctionLikeStorage $storage,
    ): bool {
        if ($method_id !== null) {
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id) ?? $method_id;
            $storage = $codebase->methods->getStorage($declaring_method_id);

            // the arguments of a call through an alias of a trait method don't flow into its body
            if ($storage->abstract || strtolower((string) $storage->cased_name) !== $method_id->method_name) {
                return false;
            }
        }

        return $storage->location !== null
            && $codebase->config->isInProjectDirs($storage->location->file_path);
    }

    /**
     * The node of what the function-like called leaves in a by-reference parameter: that of the
     * method in the class it appears in, specialized to the call like its return.
     *
     * @psalm-capabilities read-props
     */
    private static function getByRefParamOutNode(
        Codebase $codebase,
        string $cased_function_id,
        ?MethodIdentifier $method_id,
        FunctionLikeStorage $storage,
        FunctionLikeParameter $param,
        int $argument_offset,
        CodeLocation $call_location,
    ): DataFlowNode {
        if ($method_id !== null) {
            $cased_function_id = FunctionLikeAnalyzer::getByRefParamsOutMethodId($codebase, $method_id);
            $storage = $codebase->methods->getStorage(
                $codebase->methods->getDeclaringMethodId($method_id) ?? $method_id,
            );
        }

        return DataFlowNode::getForMethodArgumentOut(
            $cased_function_id,
            DataFlowNode::getParameterOffset($storage, $param, $argument_offset),
            $storage,
            $storage->specialize_call ? $call_location : null,
        );
    }

    /**
     * @return false|null
     */
    private static function evaluateArbitraryParam(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Arg $arg,
        Context $context,
    ): ?bool {
        // there are a bunch of things we want to evaluate even when we don't
        // know what function/method is being called
        if ($arg->value instanceof PhpParser\Node\Expr\Closure
            || $arg->value instanceof PhpParser\Node\Expr\ConstFetch
            || $arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
            || $arg->value instanceof PhpParser\Node\Expr\FuncCall
            || $arg->value instanceof PhpParser\Node\Expr\MethodCall
            || $arg->value instanceof PhpParser\Node\Expr\StaticCall
            || $arg->value instanceof PhpParser\Node\Expr\ArrowFunction
            || $arg->value instanceof PhpParser\Node\Expr\New_
            || $arg->value instanceof PhpParser\Node\Expr\Cast
            || $arg->value instanceof PhpParser\Node\Expr\Assign
            || $arg->value instanceof PhpParser\Node\Expr\ArrayDimFetch
            || $arg->value instanceof PhpParser\Node\Expr\PropertyFetch
            || $arg->value instanceof PhpParser\Node\Expr\Array_
            || $arg->value instanceof PhpParser\Node\Expr\BinaryOp
            || $arg->value instanceof PhpParser\Node\Expr\Ternary
            || $arg->value instanceof PhpParser\Node\Scalar\InterpolatedString
            || $arg->value instanceof PhpParser\Node\Expr\PostInc
            || $arg->value instanceof PhpParser\Node\Expr\PostDec
            || $arg->value instanceof PhpParser\Node\Expr\PreInc
            || $arg->value instanceof PhpParser\Node\Expr\PreDec
        ) {
            $was_inside_call = $context->inside_call;
            $context->inside_call = true;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $context) === false) {
                $context->inside_call = $was_inside_call;

                return false;
            }

            $context->inside_call = $was_inside_call;
        }

        if ($arg->value instanceof PhpParser\Node\Expr\PropertyFetch
            && $arg->value->name instanceof PhpParser\Node\Identifier
        ) {
            $var_id = '$' . $arg->value->name->name;
        } else {
            $var_id = ExpressionIdentifier::getVarId(
                $arg->value,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer,
            );
        }

        if ($var_id) {
            if ($arg->value instanceof PhpParser\Node\Expr\Variable) {
                $statements_analyzer->registerPossiblyUndefinedVariable($var_id, $arg->value);
            }

            if (!$context->hasVariable($var_id)
                || $context->vars_in_scope[$var_id]->isNull()
            ) {
                if (!isset($context->vars_in_scope[$var_id])
                    && $arg->value instanceof PhpParser\Node\Expr\Variable
                ) {
                    IssueBuffer::maybeAdd(
                        new PossiblyUndefinedVariable(
                            'Variable ' . $var_id
                                . ' must be defined prior to use within an unknown function or method',
                            new CodeLocation($statements_analyzer->getSource(), $arg->value),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                // we don't know if it exists, assume it's passed by reference
                $context->vars_in_scope[$var_id] = Type::getMixed();
                $context->vars_possibly_in_scope[$var_id] = true;
            } else {
                $was_inside_call = $context->inside_call;
                $context->inside_call = true;
                ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $context);
                $context->inside_call = $was_inside_call;

                $context->removeVarFromConflictingClauses(
                    $var_id,
                    $context->vars_in_scope[$var_id],
                    $statements_analyzer,
                );

                $t = $context->vars_in_scope[$var_id]->getBuilder();
                foreach ($t->getAtomicTypes() as $type) {
                    if ($type instanceof TArray && $type->isEmptyArray()) {
                        $t->removeType('array');
                        $t->addType(
                            new TArray(
                                [Type::getArrayKey(), Type::getMixed()],
                            ),
                        );
                    }
                }
                $context->vars_in_scope[$var_id] = $t->freeze();
            }
        }

        return null;
    }

    private static function handleByRefReadonlyArg(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        string $fq_class_name,
        string $prop_name,
        ?string $lhs_var_id,
    ): void {
        $property_id = $fq_class_name . '::$' . $prop_name;

        $codebase = $statements_analyzer->getCodebase();
        $declaring_property_class = (string) $codebase->properties->getDeclaringClassForProperty(
            $property_id,
            true,
            $statements_analyzer,
        );

        try {
            $declaring_class_storage = $codebase->classlike_storage_provider->get($declaring_property_class);
        } catch (InvalidArgumentException) {
            return;
        }

        if (isset($declaring_class_storage->properties[$prop_name])) {
            $property_storage = $declaring_class_storage->properties[$prop_name];

            InstancePropertyAssignmentAnalyzer::trackPropertyImpurity(
                $statements_analyzer,
                $stmt,
                $property_id,
                $property_storage,
                $declaring_class_storage,
                $context,
                $lhs_var_id,
            );
        }
    }

    /**
     * @return false|null
     */
    private static function handleByRefFunctionArg(
        StatementsAnalyzer $statements_analyzer,
        ?string $method_id,
        int $argument_offset,
        PhpParser\Node\Arg $arg,
        Context $context,
    ): ?bool {
        $var_id = ExpressionIdentifier::getVarId(
            $arg->value,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        );

        $builtin_array_functions = [
            'ksort', 'asort', 'krsort', 'arsort', 'natcasesort', 'natsort',
            'reset', 'end', 'next', 'prev', 'array_pop', 'array_shift', 'extract',
        ];

        if ($arg->value instanceof PhpParser\Node\Expr\PropertyFetch
            && $arg->value->name instanceof PhpParser\Node\Identifier) {
            $prop_name = $arg->value->name->name;

            // @todo atm only works for simple fetch, $a->foo, not $a->foo->bar
            // I guess there's a function to do this, but I couldn't locate it
            $var_id = ExpressionIdentifier::getVarId(
                $arg->value->var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer,
            );

            if (!empty($statements_analyzer->getFQCLN())) {
                $fq_class_name = $statements_analyzer->getFQCLN();

                self::handleByRefReadonlyArg(
                    $statements_analyzer,
                    $context,
                    $arg->value,
                    $fq_class_name,
                    $prop_name,
                    $var_id,
                );
            } elseif ($var_id && isset($context->vars_in_scope[$var_id])) {
                foreach ($context->vars_in_scope[$var_id]->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TNamedObject) {
                        $fq_class_name = $atomic_type->value;

                        self::handleByRefReadonlyArg(
                            $statements_analyzer,
                            $context,
                            $arg->value,
                            $fq_class_name,
                            $prop_name,
                            $var_id,
                        );
                    }
                }
            }
        }

        if (($var_id && isset($context->vars_in_scope[$var_id]))
            || ($method_id
                && in_array(
                    $method_id,
                    $builtin_array_functions,
                    true,
                ))
        ) {
            $was_inside_assignment = $context->inside_assignment;
            $context->inside_assignment = true;

            // if the variable is in scope, get or we're in a special array function,
            // figure out its type before proceeding
            if (ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $arg->value,
                $context,
            ) === false) {
                $context->inside_assignment = $was_inside_assignment;

                return false;
            }

            $context->inside_assignment = $was_inside_assignment;
        }

        // special handling for array sort
        if ($argument_offset === 0
            && $method_id
            && in_array(
                $method_id,
                $builtin_array_functions,
                true,
            )
        ) {
            if (in_array($method_id, ['array_pop', 'array_shift'], true)) {
                ArrayFunctionArgumentsAnalyzer::handleByRefArrayAdjustment(
                    $statements_analyzer,
                    $arg,
                    $context,
                    $method_id === 'array_shift',
                );

                return null;
            }

            // noops
            if (in_array($method_id, ['reset', 'end', 'next', 'prev', 'ksort'], true)) {
                return null;
            }

            if (($arg_value_type = $statements_analyzer->node_data->getType($arg->value))
                && $arg_value_type->hasArray()
            ) {
                /**
                 * @var TArray|TKeyedArray
                 */
                $array_type = $arg_value_type->getArray();

                if ($array_type instanceof TKeyedArray) {
                    $array_type = $array_type->getGenericArrayType();
                }

                $by_ref_type = new Union([$array_type]);

                AssignmentAnalyzer::assignByRefParam(
                    $statements_analyzer,
                    $arg->value,
                    $by_ref_type,
                    $by_ref_type,
                    $context,
                    false,
                );

                return null;
            }
        }

        if ($method_id === 'socket_select') {
            if (ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $arg->value,
                $context,
            ) === false) {
                return false;
            }
        }

        if (!$arg->value instanceof PhpParser\Node\Expr\Variable) {
            $suppressed_issues = $statements_analyzer->getSuppressedIssues();

            if (!in_array('EmptyArrayAccess', $suppressed_issues, true)) {
                $statements_analyzer->addSuppressedIssues(['EmptyArrayAccess']);
            }

            $v = ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $context);

            if (!in_array('EmptyArrayAccess', $suppressed_issues, true)) {
                $statements_analyzer->removeSuppressedIssues(['EmptyArrayAccess']);
            }

            if ($v === false) {
                return false;
            }
        }

        return null;
    }

    /**
     * @param   list<PhpParser\Node\Arg> $args
     * @param   array<int,FunctionLikeParameter>        $function_params
     * @param   array<string, array<string, Union>>  $class_generic_params
     */
    private static function getProvisionalTemplateResultForFunctionLike(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        Context $context,
        ?ClassLikeStorage $class_storage,
        ?string $self_fq_class_name,
        ?ClassLikeStorage $calling_class_storage,
        FunctionLikeStorage $function_storage,
        array $class_generic_params,
        ?TemplateResult $template_result,
        array $args,
        array $function_params,
        ?FunctionLikeParameter $last_param,
    ): ?TemplateResult {
        $template_types = CallAnalyzer::getTemplateTypesForCall(
            $codebase,
            $class_storage,
            $self_fq_class_name,
            $calling_class_storage,
            $function_storage->template_types ?: [],
            $class_generic_params,
        );

        if (!$template_types) {
            return null;
        }

        if (!$template_result) {
            return new TemplateResult($template_types, []);
        }

        if (!$template_result->template_types) {
            $template_result->template_types = $template_types;
        }

        foreach ($args as $argument_offset => $arg) {
            $function_param = null;

            if ($arg->name && $function_storage->allow_named_arg_calls) {
                foreach ($function_params as $candidate_param) {
                    if ($candidate_param->name === $arg->name->name) {
                        $function_param = $candidate_param;
                        break;
                    }
                }
            } elseif ($argument_offset < count($function_params)) {
                $function_param = $function_params[$argument_offset];
            } elseif ($last_param && $last_param->is_variadic) {
                $function_param = $last_param;
            }

            if (!$function_param
                || !$function_param->type
            ) {
                continue;
            }

            $arg_value_type = $statements_analyzer->node_data->getType($arg->value);

            if (!$arg_value_type) {
                continue;
            }

            $fleshed_out_param_type = TypeExpander::expandUnion(
                $codebase,
                $function_param->type,
                $class_storage->name ?? null,
                $calling_class_storage->name ?? null,
                null,
                true,
                false,
                $calling_class_storage->final ?? false,
            );

            TemplateStandinTypeReplacer::fillTemplateResult(
                $fleshed_out_param_type,
                $template_result,
                $codebase,
                $statements_analyzer,
                $arg_value_type,
                $argument_offset,
                $context->self,
                $context->calling_method_id ?: $context->calling_function_id,
                false,
            );
        }

        return $template_result;
    }

    /**
     * @param   array<int, PhpParser\Node\Arg>  $args
     * @param   array<int,FunctionLikeParameter>        $function_params
     */
    private static function checkArgCount(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        ?FunctionLikeStorage $function_storage,
        Context $context,
        ?TemplateResult $template_result,
        bool $is_variadic,
        array $args,
        array $function_params,
        bool $in_call_map,
        string|MethodIdentifier|null $method_id,
        ?string $cased_method_id,
        CodeLocation $code_location,
    ): void {
        if (!$is_variadic
            && count($args) > count($function_params)
            && (!count($function_params) || $function_params[count($function_params) - 1]->name !== '...=')
            && ($in_call_map
                || !$function_storage instanceof MethodStorage
                || $function_storage->is_static
                || ($method_id instanceof MethodIdentifier
                    && $method_id->method_name === '__construct'))
        ) {
            IssueBuffer::maybeAdd(
                new TooManyArguments(
                    'Too many arguments for ' . ($cased_method_id ?: $method_id)
                    . ' - expecting ' . count($function_params) . ' but saw ' . count($args),
                    $code_location,
                    (string)$method_id,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );

            return;
        }

        if (count($args) < count($function_params)) {
            //we're gonna loop over given args and unset them from the function_params.
            // If some mandatory params are left at the end, we'll throw an error
            foreach ($args as $arg) {
                // when the argument is not named, we can remove the params in order
                if ($arg->name === null) {
                    // if we're unpacking, we try to unset the exact number of params, if we can't we give up and return
                    if ($arg->unpack) {
                        $arg_value_type = $statements_analyzer->node_data->getType($arg->value);

                        if (!$arg_value_type || !$arg_value_type->hasArray()) {
                            return;
                        }

                        if ($arg_value_type->isSingle()
                            && ($atomic_arg_type = $arg_value_type->getSingleAtomic()) instanceof TKeyedArray
                            && !$atomic_arg_type->is_list
                        ) {
                            //if we have a single shape, we'll check param names
                            foreach ($atomic_arg_type->properties as $property_name => $_property_type) {
                                foreach ($function_params as $k => $param) {
                                    if ($param->name === $property_name) {
                                        unset($function_params[$k]);
                                    }
                                }
                            }
                            continue;
                        }

                        foreach ($arg_value_type->getAtomicTypes() as $atomic_arg_type) {
                            $packed_var_definite_args_tmp = [];
                            if ($atomic_arg_type instanceof TKeyedArray) {
                                if ($atomic_arg_type->fallback_params !== null) {
                                    return;
                                }

                                if (!$atomic_arg_type->allShapeKeysAlwaysDefined()) {
                                    return;
                                }

                                //we did not return. The number of packed params is the number of properties
                                $packed_var_definite_args_tmp[] = count($atomic_arg_type->properties);
                            } elseif ($atomic_arg_type instanceof TNonEmptyArray) {
                                if ($atomic_arg_type->count === null) {
                                    return;
                                }

                                $packed_var_definite_args_tmp[] = $atomic_arg_type->count;
                            } elseif ($atomic_arg_type instanceof TArray
                                && $atomic_arg_type->type_params[1]->isNever()
                            ) {
                                $packed_var_definite_args_tmp[] = 0;
                            } else {
                                return;
                            }


                            if (min($packed_var_definite_args_tmp) === max($packed_var_definite_args_tmp)) {
                                //we have a stable number of params
                                $packed_var_definite_args = $packed_var_definite_args_tmp[0];
                            } else {
                                return;
                            }
                        }
                    } else {
                        //if we're not unpacking, we remove the first param
                        $packed_var_definite_args = 1;
                    }

                    $function_params = array_slice($function_params, $packed_var_definite_args);
                    continue;
                }

                foreach ($function_params as $k => $param) {
                    if ($param->name === $arg->name->name) {
                        unset($function_params[$k]);
                        continue;
                    }
                }
            }

            //we're now left with an array of params that were not passed.
            // If they're mandatory, throw an error. Otherwise, we compute the default value
            foreach ($function_params as $i => $param) {
                if (!$param->is_optional && !$param->is_variadic) {
                    IssueBuffer::maybeAdd(
                        new TooFewArguments(
                            'Too few arguments for ' . $cased_method_id
                            . ' - expecting ' . $param->name . ' to be passed',
                            $code_location,
                            (string)$method_id,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                    continue;
                }

                if ($param->type
                    && $param->default_type
                    && !$param->is_variadic
                    && $template_result
                ) {
                    if ($param->default_type instanceof Union) {
                        $default_type = $param->default_type;
                    } else {
                        $default_type_atomic = ConstantTypeResolver::resolve(
                            $codebase->classlikes,
                            $param->default_type,
                            $statements_analyzer,
                        );

                        $default_type = new Union([$default_type_atomic]);
                    }

                    TemplateStandinTypeReplacer::fillTemplateResult(
                        $param->type,
                        $template_result,
                        $codebase,
                        $statements_analyzer,
                        $default_type,
                        $i,
                        $context->self,
                        $context->calling_method_id ?: $context->calling_function_id,
                        true,
                    );
                }
            }
        }
    }

    /**
     * The sink of the value of each curl option that has one:
     *
     * - `ssrf` for the options choosing where a request goes, or the protocol it uses: a URL without a scheme uses
     *   CURLOPT_DEFAULT_PROTOCOL, and CURLOPT_REDIR_PROTOCOLS_STR lets a redirect read a local file.
     * - `header` for the ones curl writes as is into a request: a line break in a header, the request line or an FTP
     *   command adds others, or a whole other request, to what is sent. CURLOPT_QUOTE sends commands as they are,
     *   and CURLOPT_COOKIELIST adds cookies to the requests.
     * - `file` for the ones naming a file curl reads or writes: CURLOPT_COOKIEJAR writes what the server sends.
     * - `sleep` for the ones making curl throttle a transfer or wait longer for it.
     * - `callable` for the functions curl calls.
     *
     * Some options have several: a `file://` URL reads a local file, the request target sent to a proxy is the URL
     * the proxy fetches, and a unix socket is a file.
     *
     * The value of another option (a body, credentials curl encodes or checks, ...) can do none of these.
     */
    private const CURL_OPTION_SINKS = [
        'CURLOPT_URL' => TaintKind::INPUT_SSRF | TaintKind::INPUT_FILE,
        'CURLOPT_PORT' => TaintKind::INPUT_SSRF,
        'CURLOPT_DEFAULT_PROTOCOL' => TaintKind::INPUT_SSRF,
        'CURLOPT_PROTOCOLS_STR' => TaintKind::INPUT_SSRF,
        'CURLOPT_REDIR_PROTOCOLS_STR' => TaintKind::INPUT_SSRF,
        'CURLOPT_PROXY' => TaintKind::INPUT_SSRF,
        'CURLOPT_PROXYPORT' => TaintKind::INPUT_SSRF,
        'CURLOPT_PRE_PROXY' => TaintKind::INPUT_SSRF,
        'CURLOPT_NOPROXY' => TaintKind::INPUT_SSRF,
        'CURLOPT_CONNECT_TO' => TaintKind::INPUT_SSRF,
        'CURLOPT_RESOLVE' => TaintKind::INPUT_SSRF,
        'CURLOPT_DNS_SERVERS' => TaintKind::INPUT_SSRF,
        'CURLOPT_DNS_INTERFACE' => TaintKind::INPUT_SSRF,
        'CURLOPT_DNS_LOCAL_IP4' => TaintKind::INPUT_SSRF,
        'CURLOPT_DNS_LOCAL_IP6' => TaintKind::INPUT_SSRF,
        'CURLOPT_DOH_URL' => TaintKind::INPUT_SSRF,
        'CURLOPT_INTERFACE' => TaintKind::INPUT_SSRF,
        'CURLOPT_UNIX_SOCKET_PATH' => TaintKind::INPUT_SSRF | TaintKind::INPUT_FILE,
        'CURLOPT_ABSTRACT_UNIX_SOCKET' => TaintKind::INPUT_SSRF,
        'CURLOPT_HTTPHEADER' => TaintKind::INPUT_HEADER,
        'CURLOPT_PROXYHEADER' => TaintKind::INPUT_HEADER,
        'CURLOPT_CUSTOMREQUEST' => TaintKind::INPUT_HEADER,
        'CURLOPT_REQUEST_TARGET' => TaintKind::INPUT_HEADER | TaintKind::INPUT_SSRF,
        'CURLOPT_USERAGENT' => TaintKind::INPUT_HEADER,
        'CURLOPT_REFERER' => TaintKind::INPUT_HEADER,
        'CURLOPT_COOKIE' => TaintKind::INPUT_HEADER,
        'CURLOPT_COOKIELIST' => TaintKind::INPUT_HEADER,
        'CURLOPT_ENCODING' => TaintKind::INPUT_HEADER,
        'CURLOPT_ACCEPT_ENCODING' => TaintKind::INPUT_HEADER,
        'CURLOPT_RANGE' => TaintKind::INPUT_HEADER,
        'CURLOPT_XOAUTH2_BEARER' => TaintKind::INPUT_HEADER,
        'CURLOPT_AWS_SIGV4' => TaintKind::INPUT_HEADER,
        'CURLOPT_RTSP_STREAM_URI' => TaintKind::INPUT_HEADER,
        'CURLOPT_RTSP_SESSION_ID' => TaintKind::INPUT_HEADER,
        'CURLOPT_RTSP_TRANSPORT' => TaintKind::INPUT_HEADER,
        'CURLOPT_QUOTE' => TaintKind::INPUT_HEADER,
        'CURLOPT_PREQUOTE' => TaintKind::INPUT_HEADER,
        'CURLOPT_POSTQUOTE' => TaintKind::INPUT_HEADER,
        'CURLOPT_COOKIEFILE' => TaintKind::INPUT_FILE,
        'CURLOPT_COOKIEJAR' => TaintKind::INPUT_FILE,
        'CURLOPT_HSTS' => TaintKind::INPUT_FILE,
        'CURLOPT_ALTSVC' => TaintKind::INPUT_FILE,
        'CURLOPT_NETRC_FILE' => TaintKind::INPUT_FILE,
        'CURLOPT_CAINFO' => TaintKind::INPUT_FILE,
        'CURLOPT_CAPATH' => TaintKind::INPUT_FILE,
        'CURLOPT_CRLFILE' => TaintKind::INPUT_FILE,
        'CURLOPT_ISSUERCERT' => TaintKind::INPUT_FILE,
        'CURLOPT_SSLCERT' => TaintKind::INPUT_FILE,
        'CURLOPT_SSLKEY' => TaintKind::INPUT_FILE,
        'CURLOPT_PINNEDPUBLICKEY' => TaintKind::INPUT_FILE,
        'CURLOPT_PROXY_CAINFO' => TaintKind::INPUT_FILE,
        'CURLOPT_PROXY_CAPATH' => TaintKind::INPUT_FILE,
        'CURLOPT_PROXY_CRLFILE' => TaintKind::INPUT_FILE,
        'CURLOPT_PROXY_ISSUERCERT' => TaintKind::INPUT_FILE,
        'CURLOPT_PROXY_SSLCERT' => TaintKind::INPUT_FILE,
        'CURLOPT_PROXY_SSLKEY' => TaintKind::INPUT_FILE,
        'CURLOPT_PROXY_PINNEDPUBLICKEY' => TaintKind::INPUT_FILE,
        'CURLOPT_SSH_PRIVATE_KEYFILE' => TaintKind::INPUT_FILE,
        'CURLOPT_SSH_PUBLIC_KEYFILE' => TaintKind::INPUT_FILE,
        'CURLOPT_SSH_KNOWNHOSTS' => TaintKind::INPUT_FILE,
        'CURLOPT_MAX_RECV_SPEED_LARGE' => TaintKind::INPUT_SLEEP,
        'CURLOPT_MAX_SEND_SPEED_LARGE' => TaintKind::INPUT_SLEEP,
        'CURLOPT_TIMEOUT' => TaintKind::INPUT_SLEEP,
        'CURLOPT_TIMEOUT_MS' => TaintKind::INPUT_SLEEP,
        'CURLOPT_CONNECTTIMEOUT' => TaintKind::INPUT_SLEEP,
        'CURLOPT_CONNECTTIMEOUT_MS' => TaintKind::INPUT_SLEEP,
        'CURLOPT_EXPECT_100_TIMEOUT_MS' => TaintKind::INPUT_SLEEP,
        'CURLOPT_SERVER_RESPONSE_TIMEOUT' => TaintKind::INPUT_SLEEP,
        'CURLOPT_FTP_RESPONSE_TIMEOUT' => TaintKind::INPUT_SLEEP,
        'CURLOPT_ACCEPTTIMEOUT_MS' => TaintKind::INPUT_SLEEP,
        'CURLOPT_WRITEFUNCTION' => TaintKind::INPUT_CALLABLE,
        'CURLOPT_READFUNCTION' => TaintKind::INPUT_CALLABLE,
        'CURLOPT_HEADERFUNCTION' => TaintKind::INPUT_CALLABLE,
        'CURLOPT_PROGRESSFUNCTION' => TaintKind::INPUT_CALLABLE,
        'CURLOPT_XFERINFOFUNCTION' => TaintKind::INPUT_CALLABLE,
        'CURLOPT_DEBUGFUNCTION' => TaintKind::INPUT_CALLABLE,
        'CURLOPT_FNMATCH_FUNCTION' => TaintKind::INPUT_CALLABLE,
        'CURLOPT_PREREQFUNCTION' => TaintKind::INPUT_CALLABLE,
        'CURLOPT_SSH_HOSTKEYFUNCTION' => TaintKind::INPUT_CALLABLE,
        'CURLOPT_SEEKFUNCTION' => TaintKind::INPUT_CALLABLE,
    ];

    /**
     * The sinks of a curl option the analysis can't tell: any of CURL_OPTION_SINKS.
     */
    private const CURL_ANY_OPTION_SINKS = TaintKind::INPUT_SSRF
        | TaintKind::INPUT_HEADER
        | TaintKind::INPUT_FILE
        | TaintKind::INPUT_SLEEP
        | TaintKind::INPUT_CALLABLE;

    /**
     * The name of each of the sinks of CURL_OPTION_SINKS, for the nodes of addCurlOptionArraySinks().
     */
    private const CURL_SINK_NAMES = [
        TaintKind::INPUT_SSRF => 'ssrf',
        TaintKind::INPUT_HEADER => 'header',
        TaintKind::INPUT_FILE => 'file',
        TaintKind::INPUT_SLEEP => 'sleep',
        TaintKind::INPUT_CALLABLE => 'callable',
    ];

    /**
     * The sinks of the argument at $argument_offset of a call of $function_id. The value given to curl_setopt()
     * is the sink of its option (see CURL_OPTION_SINKS). An option the analysis can't tell may be any of them.
     *
     * @param array<int, PhpParser\Node\Arg> $args
     * @psalm-capabilities read-props
     */
    private static function getArgumentSinks(string $function_id, int $argument_offset, array $args, int $sinks): int
    {
        if ($argument_offset !== 2 || strtolower($function_id) !== 'curl_setopt' || !isset($args[1])) {
            return $sinks;
        }

        $option = $args[1]->value;

        if (!$option instanceof PhpParser\Node\Expr\ConstFetch) {
            return self::CURL_ANY_OPTION_SINKS;
        }

        return self::CURL_OPTION_SINKS[strtoupper($option->name->toString())] ?? 0;
    }

    /**
     * Makes the value of each option given to curl_setopt_array() the sink curl_setopt() makes it (see
     * CURL_OPTION_SINKS). The options array flows into the node of an option through a fetch of its key, so what
     * the array holds under another key doesn't reach it, but what it holds under a key the analysis can't tell
     * does. An option the curl extension doesn't define can't be given, but without the curl extension the key of
     * every option is unknown, so anything in the array reaches it.
     *
     * The node of an option flows into one node for each of its sinks, which flows into the sink: a value under a
     * key the analysis can't tell, which reaches every option, is reported once for each sink.
     */
    private static function addCurlOptionArraySinks(
        TaintFlowGraph $graph,
        Codebase $codebase,
        string $function_id,
        CodeLocation $code_location,
    ): void {
        $options_node = DataFlowNode::getForCallableArg('builtin', $function_id, 1, $code_location);
        $graph->addNode($options_node);

        $constants = $codebase->config->getPredefinedConstants();
        $has_curl = isset($constants['CURLOPT_URL']);
        $sink_nodes = [];
        $fetched_options = [];

        foreach (self::CURL_OPTION_SINKS as $option_name => $sinks) {
            $option = isset($constants[$option_name]) && is_int($constants[$option_name])
                ? $constants[$option_name]
                : null;

            // an alias of another option (CURLOPT_ENCODING, ...) is fetched with it
            if (($option === null && $has_curl) || isset($fetched_options[$option ?? $option_name])) {
                continue;
            }

            $fetched_options[$option ?? $option_name] = true;

            $option_node = DataFlowNode::getForCallableArg(
                'builtin',
                $function_id . '[' . $option_name . ']',
                1,
                $code_location,
            );
            $graph->addNode($option_node);
            $graph->addPath(
                $options_node,
                $option_node,
                $option !== null ? 'arrayvalue-fetch-\'' . $option . '\'' : 'arrayvalue-fetch',
            );

            foreach (self::CURL_SINK_NAMES as $sink => $sink_name) {
                if (($sinks & $sink) === 0) {
                    continue;
                }

                if (!isset($sink_nodes[$sink])) {
                    $sink_nodes[$sink] = DataFlowNode::getForCallableArg(
                        'builtin',
                        $function_id . '[' . $sink_name . ' options]',
                        1,
                        $code_location,
                    );
                    $graph->addNode($sink_nodes[$sink]);

                    $sink_node = DataFlowNode::getForCallableArg(
                        'builtin',
                        $function_id . '[' . $sink_name . ']',
                        1,
                        $code_location,
                        $sink,
                    );
                    $graph->addSink($sink_node);
                    $graph->addPath($sink_nodes[$sink], $sink_node, 'arg');
                }

                $graph->addPath($option_node, $sink_nodes[$sink], 'arg');
            }
        }
    }

    /**
     * print_r() and var_export() return what they would output when their second argument is true: they output
     * nothing then, so their output sinks don't apply.
     *
     * @param array<int, PhpParser\Node\Arg> $args
     */
    private static function returnsInsteadOfOutputting(
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
        array $args,
    ): bool {
        $function_id = strtolower($function_id);
        if ($function_id !== 'print_r' && $function_id !== 'var_export') {
            return false;
        }

        foreach ($args as $offset => $arg) {
            if ($arg->name !== null ? $arg->name->name === 'return' : $offset === 1) {
                return $statements_analyzer->node_data->getType($arg->value)?->isTrue() ?? false;
            }
        }

        return false;
    }
}
