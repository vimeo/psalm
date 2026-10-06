<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use InvalidArgumentException;
use PhpParser;
use PhpParser\BuilderFactory;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Analyzer\FunctionAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\ConcatAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Codebase\InternalTaintSourceMap;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Plugin\EventHandler\Event\AfterFunctionCallAnalysisEvent;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\TaintKind;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_values;
use function count;
use function explode;
use function is_string;
use function preg_match;
use function preg_match_all;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function strlen;
use function strrpos;
use function strtolower;
use function substr;
use function trim;

use const PREG_SET_ORDER;

/**
 * @internal
 */
final class FunctionCallReturnTypeFetcher
{
    /**
     * @param non-empty-string $function_id
     */
    public static function fetch(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Name $function_name,
        string $function_id,
        bool $in_call_map,
        bool $is_stubbed,
        ?FunctionLikeStorage $function_storage,
        ?TCallable $callmap_callable,
        TemplateResult $template_result,
        Context $context,
    ): Union {
        $stmt_type = null;
        $config = $codebase->config;

        if ($stmt->isFirstClassCallable()) {
            $candidate_callable = CallableTypeComparator::getCallableFromAtomic(
                $codebase,
                Type::getAtomicStringFromLiteral($function_id),
                null,
                $statements_analyzer,
                $context,
                true,
            );

            if ($candidate_callable) {
                $stmt_type = new Union([new TClosure(
                    $candidate_callable->params,
                    $candidate_callable->return_type,
                    $candidate_callable->purity,
                    callable_id: strtolower($function_id),
                )]);
            } else {
                $stmt_type = Type::getClosure();
            }
        } elseif ($codebase->functions->return_type_provider->has($function_id)) {
            $stmt_type = $codebase->functions->return_type_provider->getReturnType(
                $statements_analyzer,
                $function_id,
                $stmt,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $function_name),
            );
        }

        if (!$stmt_type) {
            if (!$in_call_map || $is_stubbed) {
                if ($function_storage && $function_storage->template_types) {
                    foreach ($function_storage->template_types as $template_name => $_) {
                        if (!isset($template_result->lower_bounds[$template_name])) {
                            if ($template_name === 'TFunctionArgCount') {
                                $template_result->lower_bounds[$template_name] = [
                                    'fn-' . $function_id => [
                                        new TemplateBound(
                                            Type::getInt(false, count($stmt->getArgs())),
                                        ),
                                    ],
                                ];
                            } elseif ($template_name === 'TPhpMajorVersion') {
                                $template_result->lower_bounds[$template_name] = [
                                    'fn-' . $function_id => [
                                        new TemplateBound(
                                            Type::getInt(false, $codebase->getMajorAnalysisPhpVersion()),
                                        ),
                                    ],
                                ];
                            } elseif ($template_name === 'TPhpVersionId') {
                                $template_result->lower_bounds[$template_name] = [
                                    'fn-' . $function_id => [
                                        new TemplateBound(
                                            Type::getInt(
                                                false,
                                                $codebase->analysis_php_version_id,
                                            ),
                                        ),
                                    ],
                                ];
                            } else {
                                $template_result->lower_bounds[$template_name] = [
                                    'fn-' . $function_id => [
                                        new TemplateBound(
                                            Type::getNever(),
                                        ),
                                    ],
                                ];
                            }
                        }
                    }
                }

                if ($function_storage && !$context->isSuppressingExceptions($statements_analyzer)) {
                    $context->mergeFunctionExceptions(
                        $function_storage,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    );
                }

                try {
                    if ($function_storage && $function_storage->return_type) {
                        $return_type = $function_storage->return_type;

                        if ($template_result->lower_bounds && $function_storage->template_types) {
                            $return_type = TypeExpander::expandUnion(
                                $codebase,
                                $return_type,
                                null,
                                null,
                                null,
                            );

                            $return_type = TemplateInferredTypeReplacer::replace(
                                $return_type,
                                $template_result,
                                $codebase,
                            );
                        }

                        $return_type = TypeExpander::expandUnion(
                            $codebase,
                            $return_type,
                            null,
                            null,
                            null,
                            true,
                            false,
                            false,
                            true,
                        );

                        $return_type_location = $function_storage->return_type_location;

                        $event = new AfterFunctionCallAnalysisEvent(
                            $stmt,
                            $function_id,
                            $context,
                            $statements_analyzer->getSource(),
                            $codebase,
                            $return_type,
                            [],
                        );

                        $config->eventDispatcher->dispatchAfterFunctionCallAnalysis($event);
                        $file_manipulations = $event->getFileReplacements();

                        if ($file_manipulations) {
                            FileManipulationBuffer::add(
                                $statements_analyzer->getFilePath(),
                                $file_manipulations,
                            );
                        }

                        $return_type = $return_type->setByRef($function_storage->returns_by_ref);
                        $stmt_type = $return_type;

                        // only check the type locally if it's defined externally
                        if ($return_type_location &&
                            !$is_stubbed && // makes lookups or array_* functions quicker
                            !$config->isInProjectDirs($return_type_location->file_path)
                        ) {
                            /** @psalm-suppress UnusedMethodCall Actually generates issues */
                            $return_type->check(
                                $statements_analyzer,
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $statements_analyzer->getSuppressedIssues(),
                                $context->phantom_classes,
                                true,
                                false,
                                false,
                                $context,
                            );
                        }
                    }
                } catch (InvalidArgumentException) {
                    // this can happen when the function was defined in the Config startup script
                    $stmt_type = Type::getMixed();
                }
            } else {
                if (!$callmap_callable) {
                    throw new UnexpectedValueException('We should have a callmap callable here');
                }

                $stmt_type = self::getReturnTypeFromCallMapWithArgs(
                    $statements_analyzer,
                    $function_id,
                    $stmt->getArgs(),
                    $callmap_callable,
                    $context,
                );
            }
        }

        if (!$stmt_type) {
            $stmt_type = Type::getMixed();
        }

        if (!$stmt->isFirstClassCallable()) {
            self::taintPhpInputSource(
                $statements_analyzer,
                $stmt,
                $function_id,
                $stmt_type,
                $context,
            );
            $stmt_type = OutputStreamTaintAnalyzer::taintOpenedStream(
                $statements_analyzer,
                $function_id,
                $stmt->getArgs(),
                $stmt_type,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
            );
            OutputStreamTaintAnalyzer::taintFunctionWrite($statements_analyzer, $function_id, $stmt->getArgs());
            self::taintInternalSource(
                $statements_analyzer,
                $stmt,
                $function_id,
                $stmt_type,
                $context,
            );

            if (!$function_storage && $callmap_callable) {
                self::taintInternalFlows(
                    $statements_analyzer,
                    $stmt,
                    $function_id,
                    $callmap_callable,
                    $stmt_type,
                );
            }
        }

        if (!$statements_analyzer->data_flow_graph || !$function_storage) {
            return $stmt_type;
        }

        // For a first-class callable (`foo(...)`) the value produced here is the closure
        // itself, not foo()'s return value. Attributing foo()'s return taint to the closure
        // would be wrong (e.g. it would make invoking the closure trip the variable-call sink,
        // and would leak the source/flow re-applied on invocation back onto the closure value).
        // The underlying function's taint behavior is re-dispatched onto the invocation's
        // return value in taintCallableReturnType() instead.
        if ($stmt->isFirstClassCallable()) {
            return $stmt_type;
        }

        $return_node = self::taintReturnType(
            $statements_analyzer,
            $stmt,
            $function_id,
            $function_storage->cased_name ?? $function_id,
            $function_storage,
            $stmt_type,
            $template_result,
            $context,
        );

        if ($function_storage->proxy_calls !== null) {
            foreach ($function_storage->proxy_calls as $proxy_call) {
                $fake_call_arguments = [];
                foreach ($proxy_call['params'] as $i) {
                    $fake_call_arguments[] = $stmt->getArgs()[$i];
                }

                $fake_call_factory = new BuilderFactory();

                if (str_contains($proxy_call['fqn'], '::')) {
                    [$fqcn, $method] = explode('::', $proxy_call['fqn']);
                    $fake_call = $fake_call_factory->staticCall($fqcn, $method, $fake_call_arguments);
                } else {
                    $fake_call = $fake_call_factory->funcCall($proxy_call['fqn'], $fake_call_arguments);
                }

                $old_node_data = $statements_analyzer->node_data;
                $statements_analyzer->node_data = clone $statements_analyzer->node_data;

                ExpressionAnalyzer::analyze($statements_analyzer, $fake_call, $context);

                $statements_analyzer->node_data = $old_node_data;

                if ($return_node && $proxy_call['return']) {
                    $fake_call_type = $statements_analyzer->node_data->getType($fake_call);
                    if (null !== $fake_call_type) {
                        foreach ($fake_call_type->parent_nodes as $fake_call_node) {
                            $statements_analyzer->data_flow_graph->addPath($fake_call_node, $return_node, 'return');
                        }
                    }
                }
            }
        }

        return $stmt_type;
    }

    /**
     * @param  list<PhpParser\Node\Arg>   $call_args
     */
    private static function getReturnTypeFromCallMapWithArgs(
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
        array $call_args,
        TCallable $callmap_callable,
        Context $context,
    ): Union {
        $call_map_key = strtolower($function_id);

        $codebase = $statements_analyzer->getCodebase();

        if (!$call_args) {
            switch ($call_map_key) {
                case 'hrtime':
                    $keyed_array = TKeyedArray::make([
                        Type::getInt(),
                        Type::getInt(),
                    ], null, null, true);
                    return new Union([$keyed_array]);

                case 'get_called_class':
                    return new Union([
                        new TClassString(
                            $context->self ?: 'object',
                            $context->self ? new TNamedObject($context->self, true) : null,
                        ),
                    ]);

                case 'get_parent_class':
                    if ($context->self && $codebase->classExists($context->self, null, $context)) {
                        $classlike_storage = $codebase->classlike_storage_provider->get($context->self);

                        if ($classlike_storage->parent_classes) {
                            return new Union([
                                new TClassString(
                                    array_values($classlike_storage->parent_classes)[0],
                                ),
                            ]);
                        }
                    }
            }
        } else {
            switch ($call_map_key) {
                case 'count':
                case 'sizeof':
                    if (($first_arg_type = $statements_analyzer->node_data->getType($call_args[0]->value))) {
                        $atomic_types = $first_arg_type->getAtomicTypes();

                        if (count($atomic_types) === 1) {
                            if (isset($atomic_types['array'])) {
                                if ($atomic_types['array'] instanceof TNonEmptyArray) {
                                    return new Union([
                                        $atomic_types['array']->count !== null
                                            ? new TLiteralInt($atomic_types['array']->count)
                                            : new TIntRange(1, null),
                                    ]);
                                }

                                if ($atomic_types['array'] instanceof TKeyedArray) {
                                    if ($atomic_types['array']->is_callable) {
                                        return Type::getInt(false, 2);
                                    }
                                    $min = $atomic_types['array']->getMinCount();
                                    $max = $atomic_types['array']->getMaxCount();

                                    if ($min === $max) {
                                        return new Union([new TLiteralInt($max)]);
                                    }
                                    return Type::getIntRange($min, $max);
                                }

                                if ($atomic_types['array'] instanceof TArray
                                    && $atomic_types['array']->isEmptyArray()
                                ) {
                                    return Type::getInt(false, 0);
                                }

                                return new Union([
                                    new TIntRange(0, null),
                                ]);
                            }
                        }
                    }

                    break;

                case 'hrtime':
                    if (($first_arg_type = $statements_analyzer->node_data->getType($call_args[0]->value))) {
                        if ((string) $first_arg_type === 'true') {
                            return Type::getInt(true);
                        }

                        $keyed_array = TKeyedArray::make([
                            Type::getInt(),
                            Type::getInt(),
                        ], null, null, true);

                        if ((string) $first_arg_type === 'false') {
                            return new Union([$keyed_array]);
                        }

                        return new Union([
                            $keyed_array,
                            new TInt(),
                        ]);
                    }

                    return Type::getInt(true);

                case 'min':
                case 'max':
                    if (isset($call_args[0])) {
                        $first_arg = $call_args[0]->value;

                        if ($first_arg_type = $statements_analyzer->node_data->getType($first_arg)) {
                            if ($first_arg_type->hasArray()) {
                                $array_type = $first_arg_type->getArray();
                                if ($array_type instanceof TKeyedArray) {
                                    return $array_type->getGenericValueType();
                                }

                                if ($array_type instanceof TArray) {
                                    return $array_type->type_params[1];
                                }
                            } elseif ($first_arg_type->hasScalarType()
                                && ($second_arg = ($call_args[1]->value ?? null))
                                && ($second_arg_type = $statements_analyzer->node_data->getType($second_arg))
                                && $second_arg_type->hasScalarType()
                            ) {
                                return Type::combineUnionTypes($first_arg_type, $second_arg_type);
                            }
                        }
                    }

                    break;

                case 'get_parent_class':
                    // this is unreliable, as it's hard to know exactly what's wanted - attempted this in
                    // https://github.com/vimeo/psalm/commit/355ed831e1c69c96bbf9bf2654ef64786cbe9fd7
                    // but caused problems where it didn’t know exactly what level of child we
                    // were receiving.
                    //
                    // Really this should only work on instances we've created with new Foo(),
                    // but that requires more work
                    break;

                case 'fgetcsv':
                    $string_type = new Union([
                        new TString,
                        new TNull,
                    ], [
                        'ignore_nullable_issues' => true,
                    ]);

                    $call_map_return_type = new Union([
                        Type::getNonEmptyListAtomic(
                            $string_type,
                        ),
                        new TFalse,
                        new TNull,
                    ], [
                        'ignore_nullable_issues' => $codebase->config->ignore_internal_nullable_issues,
                        'ignore_falsable_issues' => $codebase->config->ignore_internal_falsable_issues,
                    ]);

                    return $call_map_return_type;
                case 'mb_strtolower':
                    $string_arg_type = $statements_analyzer->node_data->getType($call_args[0]->value);
                    if ($string_arg_type !== null && $string_arg_type->isNonEmptyString()) {
                        $returnType = Type::getNonEmptyLowercaseString();
                    } else {
                        $returnType = Type::getLowercaseString();
                    }
                    if (count($call_args) < 2) {
                        return $returnType;
                    } else {
                        $second_arg_type = $statements_analyzer->node_data->getType($call_args[1]->value);
                        if ($second_arg_type && $second_arg_type->isNull()) {
                            return $returnType;
                        }
                    }
                    if ($string_arg_type !== null && $string_arg_type->isNonEmptyString()) {
                        return Type::getNonEmptyString();
                    } else {
                        return Type::getString();
                    }
            }
        }

        $stmt_type = $callmap_callable->return_type ?: Type::getMixed();

        switch ($function_id) {
            case 'mb_strpos':
            case 'mb_strrpos':
            case 'mb_stripos':
            case 'mb_strripos':
            case 'strpos':
            case 'strrpos':
            case 'stripos':
            case 'strripos':
            case 'strstr':
            case 'stristr':
            case 'strrchr':
            case 'strpbrk':
            case 'array_search':
                break;

            default:
                if ($stmt_type->isFalsable()
                    && $codebase->config->ignore_internal_falsable_issues
                ) {
                    $stmt_type = $stmt_type->setProperties(['ignore_falsable_issues' => true]);
                }
        }

        return $stmt_type;
    }

    /**
     * Re-dispatches the underlying function's taint behavior when a callable value is
     * invoked (e.g. a first-class callable `$f = file_get_contents(...); $f('php://input');`,
     * or `$f = fgets(...); $f(STDIN);`).
     *
     * The regular named-call wiring is skipped for callable-valued invocations because the
     * call target is an expression rather than a {@see PhpParser\Node\Name}, so none of the
     * underlying function's taint behavior is applied. This re-creates it generically for the
     * underlying function id:
     *  - the return value receives taint sources (@psalm-taint-source, and the conditional
     *    php://input source), argument-to-return flows (@psalm-flow) and the implicit
     *    param->return flow of an analyzed function body;
     *  - each argument is connected to the function's per-parameter node, which feeds an
     *    analyzed body and registers any @psalm-taint-sink parameters as sinks.
     *
     * @param non-empty-lowercase-string $callable_id
     */
    public static function taintCallableReturnType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Expr $real_stmt,
        string $callable_id,
        Context $context,
    ): void {
        if ($stmt->isFirstClassCallable()) {
            return;
        }

        if (!$graph = $statements_analyzer->getTaintFlowGraphWithSuppressed()) {
            return;
        }

        $stmt_type = $statements_analyzer->node_data->getType($real_stmt);

        if ($stmt_type === null) {
            return;
        }

        // callmap-only conditional source (fopen/file_get_contents('php://input'))
        self::taintPhpInputSource(
            $statements_analyzer,
            $stmt,
            $callable_id,
            $stmt_type,
            $context,
        );
        // callmap-only conditional sinks (writes to fopen('php://output'))
        $stmt_type = OutputStreamTaintAnalyzer::taintOpenedStream(
            $statements_analyzer,
            $callable_id,
            $stmt->getArgs(),
            $stmt_type,
            new CodeLocation($statements_analyzer->getSource(), $stmt),
        );
        OutputStreamTaintAnalyzer::taintFunctionWrite($statements_analyzer, $callable_id, $stmt->getArgs());
        // callmap-only unconditional sources (socket_read(), curl_exec(), ...)
        self::taintInternalSource(
            $statements_analyzer,
            $stmt,
            $callable_id,
            $stmt_type,
            $context,
        );

        // Re-apply the declared taint behavior of the underlying function. When the call
        // target is an expression (a callable value) rather than a Node\Name, the regular
        // named-call wiring is skipped, so nothing connects the return value to the
        // function's taint sources / return flows, and nothing connects the arguments to the
        // function's argument sinks. Re-create those connections generically here.
        $storage = self::getCallableStorage($statements_analyzer, $callable_id);

        if ($storage === null) {
            // a closure: what its body returns (which a return type it declares doesn't hold), from what its
            // parameters are given
            $closure_storage = self::getClosureStorage($statements_analyzer, $callable_id);

            if ($closure_storage !== null) {
                $closure_return_node = DataFlowNode::getForMethodReturn($callable_id, $closure_storage);
                $graph->addNode($closure_return_node);
                $stmt_type = $stmt_type->addParentNodes([$closure_return_node->id => $closure_return_node]);

                self::taintCallableParams(
                    $statements_analyzer,
                    $graph,
                    $callable_id,
                    $closure_storage,
                    $stmt->getArgs(),
                    null,
                );
                self::taintCallableByRefParams(
                    $statements_analyzer,
                    $graph,
                    $context,
                    $callable_id,
                    $closure_storage,
                    $stmt->getArgs(),
                    null,
                );
            }

            $statements_analyzer->node_data->setType($real_stmt, $stmt_type);

            return;
        }

        $args = $stmt->getArgs();

        $node_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

        $specialization_location = TaintFlowGraph::isCallSpecialized(
            $graph,
            $statements_analyzer->getCodebase(),
            $storage,
            $node_location,
        ) ? $node_location : null;

        // Return value: taint sources (@psalm-taint-source), argument-to-return flows
        // (@psalm-flow), and the implicit param->return flow of an analyzed function body.
        // The body links its per-argument entry nodes (getForMethodArgument) to this return
        // node, so wiring the actual arguments to those entry nodes below completes the flow.
        $return_node = DataFlowNode::getForMethodReturn(
            $callable_id,
            $storage,
            $specialization_location,
        );
        $graph->addNode($return_node);

        self::taintUsingStorage($storage, $graph, $return_node);

        // @psalm-flow: connect the actual argument nodes directly to the return. The
        // per-function argument nodes taintUsingFlows() relies on are not created for a
        // callable-valued invocation, so wire the arguments to the return here.
        foreach ($storage->return_source_params as $i => $path_type) {
            foreach (self::callableArgIndices($storage->params, $args, $i) as $arg_index) {
                $arg_type = $statements_analyzer->node_data->getType($args[$arg_index]->value);

                if ($arg_type === null) {
                    continue;
                }

                foreach ($arg_type->parent_nodes as $parent_node) {
                    $graph->addPath($parent_node, $return_node, $path_type);
                }
            }
        }

        $stmt_type = $stmt_type->addParentNodes([$return_node->id => $return_node]);

        self::taintCallableParams(
            $statements_analyzer,
            $graph,
            $callable_id,
            $storage,
            $args,
            $specialization_location,
        );

        self::taintCallableByRefParams(
            $statements_analyzer,
            $graph,
            $context,
            $callable_id,
            $storage,
            $args,
            $specialization_location,
        );

        $statements_analyzer->node_data->setType($real_stmt, $stmt_type);
    }

    /**
     * A call of a callable parameter of the function-like analysed, which may be any callable: it gives what the
     * callables passed to the parameter return, and passes its arguments to their parameters (see
     * taintCallablePassedToParam()).
     */
    public static function taintCallableParamCall(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Expr $real_stmt,
        Context $context,
    ): void {
        if ($stmt->isFirstClassCallable()
            || !$stmt->name instanceof PhpParser\Node\Expr\Variable
            || !is_string($stmt->name->name)
            || !($graph = $statements_analyzer->getTaintFlowGraphWithSuppressed())
        ) {
            return;
        }

        $source = $statements_analyzer->getSource();

        if ($source instanceof MethodAnalyzer) {
            $method_id = FunctionLikeAnalyzer::getByRefParamsOutMethodId(
                $statements_analyzer->getCodebase(),
                $source->getMethodId(),
            );
        } elseif ($source instanceof FunctionAnalyzer || $source instanceof ClosureAnalyzer) {
            $method_id = $source->getCorrectlyCasedMethodId();
        } else {
            return;
        }

        $var_id = '$' . $stmt->name->name;
        $var_type = $context->vars_in_scope[$var_id] ?? null;
        $storage = $source->getStorage();

        foreach ($storage->params as $offset => $param) {
            if ($var_type === null || $param->name !== $stmt->name->name || $param->location === null) {
                continue;
            }

            // the variable may hold what the parameter was given
            if (!isset($var_type->parent_nodes[DataFlowNode::getForAssignment($var_id, $param->location)->id])) {
                continue;
            }

            $return_node = DataFlowNode::getForCallableParamReturn($method_id, $offset, $storage);
            $graph->addNode($return_node);

            $stmt_type = $statements_analyzer->node_data->getType($real_stmt);

            if ($stmt_type !== null) {
                $statements_analyzer->node_data->setType(
                    $real_stmt,
                    $stmt_type->addParentNodes([$return_node->id => $return_node]),
                );
            }

            foreach ($stmt->getArgs() as $argument_offset => $arg) {
                $arg_type = $statements_analyzer->node_data->getType($arg->value);

                if ($arg_type === null || !$arg_type->parent_nodes) {
                    continue;
                }

                $argument_node = DataFlowNode::getForCallableParamArgument(
                    $method_id,
                    $offset,
                    $argument_offset,
                    $storage,
                );
                $graph->addNode($argument_node);

                foreach ($arg_type->parent_nodes as $parent_node) {
                    $graph->addPath($parent_node, $argument_node, 'arg');
                }
            }
        }
    }

    /**
     * A callable passed to a parameter of a function-like: the calls of the parameter in its body give what the
     * callable returns, and what they pass flows into its parameters (see taintCallableParamCall()).
     */
    public static function taintCallablePassedToParam(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        string $cased_method_id,
        int $param_offset,
        FunctionLikeStorage $storage,
        ?CodeLocation $specialization_location,
        Union $input_type,
    ): void {
        foreach ($input_type->getAtomicTypes() as $atomic) {
            if (!$atomic instanceof TClosure && !$atomic instanceof TCallable) {
                continue;
            }

            $return_node = DataFlowNode::getForCallableParamReturn(
                $cased_method_id,
                $param_offset,
                $storage,
                $specialization_location,
            );
            $graph->addNode($return_node);

            foreach ($atomic->return_type?->parent_nodes ?? [] as $parent_node) {
                $graph->addPath($parent_node, $return_node, 'callable-return');
            }

            if ($atomic->callable_id === null) {
                continue;
            }

            $callable_storage = self::getCallableStorage($statements_analyzer, $atomic->callable_id);

            if ($callable_storage !== null) {
                $callable_return_node = DataFlowNode::getForMethodReturn($atomic->callable_id, $callable_storage);
                $graph->addNode($callable_return_node);
                self::taintUsingStorage($callable_storage, $graph, $callable_return_node);
                $graph->addPath($callable_return_node, $return_node, 'callable-return');
            } else {
                $callable_storage = self::getClosureStorage($statements_analyzer, $atomic->callable_id);

                if ($callable_storage === null) {
                    continue;
                }

                // what the body of the closure returns, which a return type it declares doesn't hold
                $callable_return_node = DataFlowNode::getForMethodReturn($atomic->callable_id, $callable_storage);
                $graph->addNode($callable_return_node);
                $graph->addPath($callable_return_node, $return_node, 'callable-return');
            }

            foreach ($callable_storage->params as $i => $param) {
                if ($param->location === null) {
                    continue;
                }

                $argument_node = DataFlowNode::getForCallableParamArgument(
                    $cased_method_id,
                    $param_offset,
                    $i,
                    $storage,
                    $specialization_location,
                );
                $graph->addNode($argument_node);

                $param_node = DataFlowNode::getForMethodArgument($atomic->callable_id, $i, $callable_storage);
                $graph->addNode($param_node);

                if ($param->sinks) {
                    $graph->addSink($param_node);
                }

                $graph->addPath($argument_node, $param_node, 'arg');
            }
        }
    }

    /**
     * Argument entry / sinks: connect each argument to the function's per-parameter node
     * (getForMethodArgument). This carries taint into an analyzed body (whose param->return
     * path completes the implicit return flow) and into any @psalm-taint-sink params,
     * which are registered as sinks here.
     *
     * @param list<PhpParser\Node\Arg> $args
     */
    private static function taintCallableParams(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        string $callable_id,
        FunctionLikeStorage $storage,
        array $args,
        ?CodeLocation $specialization_location,
    ): void {
        foreach ($storage->params as $i => $param) {
            if ($param->location === null) {
                continue;
            }

            foreach (self::callableArgIndices($storage->params, $args, $i) as $arg_index) {
                $arg_type = $statements_analyzer->node_data->getType($args[$arg_index]->value);

                if ($arg_type === null || !$arg_type->parent_nodes) {
                    continue;
                }

                $param_node = DataFlowNode::getForMethodArgument(
                    $callable_id,
                    $i,
                    $storage,
                    $specialization_location,
                );
                $graph->addNode($param_node);

                if ($param->sinks) {
                    $graph->addSink($param_node);
                }

                if ($param->value_sinks) {
                    $graph->addArgumentValuesSink($param_node, $param->value_sinks);
                }

                foreach ($arg_type->parent_nodes as $parent_node) {
                    $graph->addPath($parent_node, $param_node, 'arg');
                }
            }
        }

        // the callables passed, which the body may call (see taintCallableParamCall())
        foreach ($storage->params as $i => $_) {
            foreach (self::callableArgIndices($storage->params, $args, $i) as $arg_index) {
                $arg_type = $statements_analyzer->node_data->getType($args[$arg_index]->value);

                if ($arg_type !== null) {
                    self::taintCallablePassedToParam(
                        $statements_analyzer,
                        $graph,
                        $callable_id,
                        $i,
                        $storage,
                        $specialization_location,
                        $arg_type,
                    );
                }
            }
        }
    }

    /**
     * The by-reference parameters of a callable invoked: what its body leaves in them flows into the
     * variables passed (see FunctionLikeAnalyzer::taintByRefParamsOut()). The value passed may still be
     * there, as another of the callables the call target may be can run.
     *
     * @param list<PhpParser\Node\Arg> $args
     */
    private static function taintCallableByRefParams(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        Context $context,
        string $callable_id,
        FunctionLikeStorage $storage,
        array $args,
        ?CodeLocation $specialization_location,
    ): void {
        foreach ($storage->params as $i => $param) {
            if (!$param->by_ref) {
                continue;
            }

            foreach (self::callableArgIndices($storage->params, $args, $i) as $arg_index) {
                $var_id = ExpressionIdentifier::getExtendedVarId(
                    $args[$arg_index]->value,
                    null,
                    $statements_analyzer,
                );

                if ($var_id === null || !isset($context->vars_in_scope[$var_id])) {
                    continue;
                }

                $out_node = DataFlowNode::getForMethodArgumentOut(
                    $callable_id,
                    $i,
                    $storage,
                    $specialization_location,
                );
                $graph->addNode($out_node);

                $context->vars_in_scope[$var_id] = $context->vars_in_scope[$var_id]->addParentNodes(
                    [$out_node->id => $out_node],
                );
            }
        }
    }

    /**
     * The storage of a closure from its id (see ClosureAnalyzer::getClosureId()), which starts with the
     * path of the file it is in.
     */
    private static function getClosureStorage(
        StatementsAnalyzer $statements_analyzer,
        string $closure_id,
    ): ?FunctionLikeStorage {
        if (!str_ends_with($closure_id, ':-:closure')) {
            return null;
        }

        $file_path = substr($closure_id, 0, -strlen(':-:closure'));
        $file_path = substr($file_path, 0, (int) strrpos($file_path, ':'));
        $file_path = substr($file_path, 0, (int) strrpos($file_path, ':'));

        try {
            return $statements_analyzer->getCodebase()->getClosureStorage($file_path, $closure_id);
        } catch (UnexpectedValueException) {
            return null;
        }
    }

    /**
     * The indices of the arguments among $args given to the parameter at offset $i of $params: the one at its
     * position or named after it, and if it is variadic those after it and those named after no other parameter. An
     * unpacked argument may hold the arguments of every parameter from its position on.
     *
     * @param array<int, FunctionLikeParameter> $params
     * @param array<int, PhpParser\Node\Arg> $args
     * @return list<int>
     * @psalm-mutation-free
     */
    private static function callableArgIndices(array $params, array $args, int $i): array
    {
        $param = $params[$i] ?? null;
        $indices = [];

        foreach ($args as $arg_index => $arg) {
            if ($arg->unpack) {
                $given = $arg_index <= $i;
            } elseif ($arg->name === null) {
                $given = $arg_index === $i || ($param !== null && $param->is_variadic && $arg_index > $i);
            } elseif ($param === null) {
                $given = false;
            } else {
                $given = $arg->name->name === $param->name
                    || ($param->is_variadic && !self::hasParamNamed($params, $arg->name->name));
            }

            if ($given) {
                $indices[] = $arg_index;
            }
        }

        return $indices;
    }

    /**
     * @param array<int, FunctionLikeParameter> $params
     * @psalm-mutation-free
     */
    private static function hasParamNamed(array $params, string $name): bool
    {
        foreach ($params as $param) {
            if ($param->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolves the storage for a called function id, or null if it has none
     * (e.g. a callmap-only builtin).
     *
     * @param non-empty-lowercase-string $function_id
     */
    private static function getCallableStorage(
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
    ): ?FunctionLikeStorage {
        $codebase = $statements_analyzer->getCodebase();

        if (!$codebase->functions->functionExists($statements_analyzer, $function_id)) {
            return null;
        }

        try {
            return $codebase->functions->getStorage($statements_analyzer, $function_id);
        } catch (UnexpectedValueException) {
            // callmap-only builtins have no storage
            return null;
        }
    }


    // function id => offset of the argument holding the stream path.
    // Only functions that *return* the stream contents belong here (readfile()
    // writes to the output buffer and returns a byte count, so it is excluded).
    /** @var array<string, int> */
    private const SOURCE_PATH_ARG = [
        'fopen' => 0,
        'file_get_contents' => 0,
        'file' => 0,
    ];

    /**
     * fopen()/file_get_contents()/file() called with a literal 'php://input' or
     * 'php://stdin' path read user-controlled data, so their return value becomes a
     * taint source.
     *
     * This is not expressible as a stub because the source is conditional on the
     * literal argument *value*; stream reading functions that merely relay their
     * handle's taint (fgets, fread, stream_get_contents, ...) are instead annotated
     * with @psalm-flow in the stubs.
     *
     * These are callmap-only functions, so they never get a FunctionLikeStorage and
     * are skipped by {@see self::taintReturnType()}.
     */
    private static function taintPhpInputSource(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        string $function_id,
        Union &$stmt_type,
        Context $context,
    ): void {
        if (!$graph = $statements_analyzer->getTaintFlowGraphWithSuppressed()) {
            return;
        }

        $function_id = strtolower($function_id);

        if (!isset(self::SOURCE_PATH_ARG[$function_id])) {
            return;
        }

        $offset = self::SOURCE_PATH_ARG[$function_id];
        $args = $stmt->getArgs();

        if (!isset($args[$offset])) {
            return;
        }

        $arg_type = $statements_analyzer->node_data->getType($args[$offset]->value);

        if (!$arg_type || !$arg_type->isSingleStringLiteral()) {
            return;
        }

        $path = strtolower($arg_type->getSingleStringLiteral()->value);

        if ($path !== 'php://input' && $path !== 'php://stdin') {
            return;
        }

        $stmt_type = InternalTaintSourceMap::addSource(
            $graph,
            $statements_analyzer,
            $stmt,
            $function_id . '(' . $path . ')',
            TaintKind::ALL_INPUT,
            $context,
            $stmt_type,
        );
    }

    /**
     * The builtins that read from outside the program (sockets, network streams, http clients)
     * return user-controlled data: see dictionaries/InternalTaintSourceMap.php.
     */
    private static function taintInternalSource(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        string $function_id,
        Union &$stmt_type,
        Context $context,
    ): void {
        $taints = InternalTaintSourceMap::getTaints($function_id, 'return');

        if ($taints === 0 || !$graph = $statements_analyzer->getTaintFlowGraphWithSuppressed()) {
            return;
        }

        $stmt_type = InternalTaintSourceMap::addSource(
            $graph,
            $statements_analyzer,
            $stmt,
            $function_id,
            $taints,
            $context,
            $stmt_type,
        );
    }

    /**
     * The builtins only declared by the call map have no storage for `@psalm-flow`: the taints of the arguments
     * given to the parameters dictionaries/InternalTaintFlowMap.php lists flow into what this call returns.
     */
    private static function taintInternalFlows(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        string $function_id,
        TCallable $callmap_callable,
        Union &$stmt_type,
    ): void {
        if (!$graph = $statements_analyzer->getTaintFlowGraphWithSuppressed()) {
            return;
        }

        $params = $callmap_callable->params ?? [];
        $flows = InternalCallMapHandler::getReturnTaintFlows($function_id, $params);

        if ($flows === []) {
            return;
        }

        // the return value of a builtin whose calls return what was given to the others is the same for every call
        $return_node = DataFlowNode::getForCallableReturn(
            'builtin',
            $function_id,
            InternalCallMapHandler::keepsStateBetweenCalls($function_id)
                ? null
                : new CodeLocation($statements_analyzer->getSource(), $stmt),
        );
        $graph->addNode($return_node);

        $removed_taints = InternalCallMapHandler::getReturnRemovedTaints($function_id);

        $args = $stmt->getArgs();
        foreach ($flows as $offset => $path_type) {
            foreach (self::callableArgIndices($params, $args, $offset) as $arg_offset) {
                $arg_type = $statements_analyzer->node_data->getType($args[$arg_offset]->value);
                if ($arg_type === null) {
                    continue;
                }

                foreach ($arg_type->parent_nodes as $parent_node) {
                    $graph->addPath(
                        $parent_node,
                        $return_node,
                        $path_type,
                        0,
                        $removed_taints | $arg_type->getTaintsToRemove(),
                    );
                }
            }
        }

        $stmt_type = $stmt_type->addParentNodes([$return_node->id => $return_node]);
    }

    private static function taintReturnType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        string $function_id,
        string $cased_function_id,
        FunctionLikeStorage $function_storage,
        Union &$stmt_type,
        TemplateResult $template_result,
        Context $context,
    ): ?DataFlowNode {
        if (!$graph = $statements_analyzer->getDataFlowGraphWithSuppressed()) {
            return null;
        }
        $taint_flow_graph = $statements_analyzer->getTaintFlowGraphWithSuppressed();

        $codebase = $statements_analyzer->getCodebase();
        $event = new AddRemoveTaintsEvent($stmt, $context, $statements_analyzer, $codebase);

        $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
        $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

        $node_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

        $specialization_location = TaintFlowGraph::isCallSpecialized(
            $taint_flow_graph,
            $codebase,
            $function_storage,
            $node_location,
        ) ? $node_location : null;

        $function_call_node = DataFlowNode::getForMethodReturn(
            $cased_function_id,
            $function_storage,
            $specialization_location,
        );
        $graph->addNode($function_call_node);

        $codebase = $statements_analyzer->getCodebase();

        $conditionally_removed_taints = 0;

        foreach ($function_storage->conditionally_removed_taints as $conditionally_removed_taint) {
            $conditionally_removed_taint = TemplateInferredTypeReplacer::replace(
                $conditionally_removed_taint,
                $template_result,
                $codebase,
            );

            $expanded_type = TypeExpander::expandUnion(
                $codebase,
                $conditionally_removed_taint,
                null,
                null,
                null,
                true,
                true,
            );

            if (!$expanded_type->isNullable()) {
                foreach ($expanded_type->getLiteralStrings() as $literal_string) {
                    $taint = $codebase->getOrRegisterTaint($literal_string->value, $function_storage->location);
                    if ($taint !== null) {
                        $conditionally_removed_taints |= $taint;
                    }
                }
            }
        }

        if ($conditionally_removed_taints && $function_storage->location) {
            $assignment_node = DataFlowNode::getForAssignment(
                $function_id . '-escaped',
                $function_storage->signature_return_type_location ?: $function_storage->location,
                $function_call_node->specialization_key,
            );

            $graph->addPath(
                $function_call_node,
                $assignment_node,
                'conditionally-escaped',
                $added_taints,
                $removed_taints | $conditionally_removed_taints,
            );

            $stmt_type = $stmt_type->addParentNodes([$assignment_node->id => $assignment_node]);
        } else {
            $stmt_type = $stmt_type->addParentNodes([$function_call_node->id => $function_call_node]);
        }

        if (!$taint_flow_graph) {
            return $function_call_node;
        }

        if ($function_storage->return_source_params && !$stmt->isFirstClassCallable()) {
            $removed_taints = $function_storage->removed_taints;

            $args = $stmt->getArgs();
            if ($function_id === 'preg_replace' && count($args) > 2) {
                $first_stmt_type = $statements_analyzer->node_data->getType($args[0]->value);
                $second_stmt_type = $statements_analyzer->node_data->getType($args[1]->value);

                if ($first_stmt_type
                    && $second_stmt_type
                    && $first_stmt_type->isSingleStringLiteral()
                    && $second_stmt_type->isSingleStringLiteral()
                ) {
                    $first_arg_value = $first_stmt_type->getSingleStringLiteral()->value;

                    $pattern = substr($first_arg_value, 1, -1);
                    if (strlen(trim($pattern)) > 0) {
                        $pattern = trim($pattern);
                        if ($pattern[0] === '['
                            && $pattern[1] === '^'
                            && str_ends_with($pattern, ']')
                        ) {
                            $pattern = substr($pattern, 2, -1);

                            if (self::simpleExclusion($pattern, $first_arg_value[0])) {
                                $removed_taints |= TaintKind::INPUT_HTML;
                                $removed_taints |= TaintKind::INPUT_HAS_QUOTES;
                                $removed_taints |= TaintKind::INPUT_SQL;
                            }
                        }
                    }
                }
            }

            // the values formatted after the start of a URL fixing its server can't choose it
            if (($function_id === 'sprintf' || $function_id === 'vsprintf') && isset($args[0])) {
                $prefixes = [];

                foreach (ConcatAnalyzer::getLiteralPrefixes($statements_analyzer, $args[0]->value) as $format) {
                    // the text formatted before the first conversion specification
                    preg_match('~^(?:[^%]|%%)*~', $format, $matches);
                    $prefixes[] = str_replace('%%', '%', $matches[0] ?? '');
                }

                $removed_taints |= ConcatAnalyzer::getTaintsRemovedAfterUrlOrigins($prefixes);
            }

            $format_type = $function_id === 'sprintf' && isset($args[0])
                ? $statements_analyzer->node_data->getType($args[0]->value)
                : null;

            $arg_removed_taints = $format_type && $format_type->allStringLiterals()
                ? self::getTaintsRemovedBySprintfFormats(ConcatAnalyzer::getLiteralValues($format_type), $args)
                : [];

            $event = new AddRemoveTaintsEvent($stmt, $context, $statements_analyzer, $codebase);

            $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
            $removed_taints |= $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

            self::taintUsingFlows(
                $function_storage,
                $taint_flow_graph,
                $function_id,
                $args,
                $specialization_location,
                $function_call_node,
                $removed_taints | $conditionally_removed_taints,
                $added_taints,
                $arg_removed_taints,
            );
        }

        self::taintUsingStorage($function_storage, $taint_flow_graph, $function_call_node);

        return $function_call_node;
    }

    /**
     * The taints the values of a sprintf() call can't have once formatted with any of $formats: those of a number, for
     * the values they only format as numbers (or not at all)
     *
     * @param list<string> $formats
     * @param array<PhpParser\Node\Arg> $args
     * @return array<int, int> by index in $args
     * @psalm-mutation-free
     */
    private static function getTaintsRemovedBySprintfFormats(array $formats, array $args): array
    {
        $string_args = [];

        foreach ($formats as $format) {
            preg_match_all(
                '~%(?:(\d+)\$)?(?:[-+ 0]|\'.)*\d*(?:\.\d*)?([bcdeEfFgGhHosuxX%])?~s',
                $format,
                $matches,
                PREG_SET_ORDER,
            );

            $next_arg = 1;

            foreach ($matches as $match) {
                $specifier = $match[2] ?? '';

                if ($specifier === '%') {
                    continue;
                }

                // `*` width or precision, taken from an argument, or an invalid conversion
                if ($specifier === '') {
                    return [];
                }

                $position = $match[1] ?? '';
                $arg = $position === '' ? $next_arg++ : (int) $position;

                if ($specifier === 'c' || $specifier === 's') {
                    $string_args[$arg] = true;
                }
            }
        }

        $removed_taints = [];

        foreach (array_values($args) as $i => $arg) {
            if ($arg->unpack || $arg->name) {
                return [];
            }

            if ($i > 0 && !isset($string_args[$i])) {
                $removed_taints[$i] = TaintKind::ALL_INPUT & ~TaintKind::NUMERIC_ONLY;
            }
        }

        return $removed_taints;
    }

    /**
     * The parameters of builtins whose taints the return value holds as they are given, though the builtin escapes
     * those of its other parameters: http_build_query() encodes the keys and values of $data, but neither the prefix
     * it adds to numeric keys nor the separator.
     */
    private const UNESCAPED_RETURN_FLOWS = [
        'http_build_query' => ['numeric_prefix' => true, 'arg_separator' => true],
    ];

    /**
     * @param array<int, PhpParser\Node\Arg> $args
     * @param array<int, int> $arg_removed_taints the taints removed from the flows of some of $args only, by index
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public static function taintUsingFlows(
        FunctionLikeStorage $function_storage,
        TaintFlowGraph $graph,
        string $function_id,
        array $args,
        ?CodeLocation $specialization_location,
        DataFlowNode $function_call_node,
        int $removed_taints,
        int $added_taints = 0,
        array $arg_removed_taints = [],
    ): void {
        foreach ($function_storage->return_source_params as $i => $path_type) {
            $arg_indices = self::callableArgIndices($function_storage->params, $args, $i);

            // the node of an argument is that of its parameter, unless it is one of those a variadic one is given
            if (!$function_storage->params[$i]->is_variadic) {
                $arg_indices = $arg_indices === [] ? [] : [$i];
            }

            $param_name = $function_storage->params[$i]->name;
            $path_removed_taints = isset(self::UNESCAPED_RETURN_FLOWS[$function_id][$param_name])
                ? $removed_taints & ~$function_storage->removed_taints
                : $removed_taints;

            foreach ($arg_indices as $arg_index) {
                $function_param_sink = DataFlowNode::getForMethodArgument(
                    $function_id,
                    $arg_index,
                    $function_storage,
                    $specialization_location,
                );

                $graph->addNode($function_param_sink);

                $graph->addPath(
                    $function_param_sink,
                    $function_call_node,
                    $path_type,
                    $added_taints | $function_storage->added_taints,
                    // what the native return type cannot hold, since PHP enforces it
                    $path_removed_taints | ($function_storage->signature_return_type?->getTaintsToRemove() ?? 0)
                        | ($arg_removed_taints[$arg_index] ?? 0),
                );
            }
        }
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public static function taintUsingStorage(
        FunctionLikeStorage $function_storage,
        TaintFlowGraph $graph,
        DataFlowNode $function_call_node,
    ): void {
        // Docblock-defined taints should override inherited
        $added_taints = 0;
        if ($function_storage->taint_source_types !== 0) {
            $added_taints = $function_storage->taint_source_types;
        } elseif ($function_storage->added_taints !== 0) {
            $added_taints = $function_storage->added_taints;
        }

        // a source can only return taints its native return type can hold: a `string` is never a NoSQL query
        $taints = $added_taints
            & ~$function_storage->removed_taints
            & ~($function_storage->signature_return_type?->getTaintsToRemove() ?? 0);
        if ($taints !== 0) {
            $taint_source = $function_call_node->setTaints($taints);
            $graph->addSource($taint_source);
        }
    }

    /**
     * @psalm-pure
     */
    private static function simpleExclusion(string $pattern, string $escape_char): bool
    {
        $str_length = strlen($pattern);

        for ($i = 0; $i < $str_length; $i++) {
            $current = $pattern[$i];
            $next = $pattern[$i + 1] ?? null;

            if ($current === '\\') {
                if ($next === null
                    || $next === 'x'
                    || $next === 'u'
                ) {
                    return false;
                }

                if ($next === '.'
                    || $next === '('
                    || $next === ')'
                    || $next === '['
                    || $next === ']'
                    || $next === 's'
                    || $next === 'w'
                    || $next === $escape_char
                ) {
                    $i++;
                    continue;
                }

                return false;
            }

            if ($next !== '-') {
                if ($current === '_'
                    || $current === '-'
                    || $current === '|'
                    || $current === ':'
                    || $current === '#'
                    || $current === '.'
                    || $current === ' '
                ) {
                    continue;
                }

                return false;
            }

            if ($current === ']') {
                return false;
            }

            if (!isset($pattern[$i + 2])) {
                return false;
            }

            if (($current === 'a' && $pattern[$i + 2] === 'z')
                || ($current === 'a' && $pattern[$i + 2] === 'Z')
                || ($current === 'A' && $pattern[$i + 2] === 'Z')
                || ($current === '0' && $pattern[$i + 2] === '9')
            ) {
                $i += 2;
                continue;
            }

            return false;
        }

        return true;
    }
}
