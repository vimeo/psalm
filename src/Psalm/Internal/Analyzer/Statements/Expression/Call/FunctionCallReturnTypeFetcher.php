<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use PhpParser\BuilderFactory;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\DataFlow\TaintSource;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Plugin\EventHandler\Event\AfterFunctionCallAnalysisEvent;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use function count;
use function strtolower;
use function strpos;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TemplateResult;
use function explode;

/**
 * @internal
 */
class FunctionCallReturnTypeFetcher
{
    /**
     * @param non-empty-string $function_id
     */
    public static function fetch(
        StatementsAnalyzer $statements_analyzer,
        \Psalm\Codebase $codebase,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Name $function_name,
        string $function_id,
        bool $in_call_map,
        bool $is_stubbed,
        ?FunctionLikeStorage $function_storage,
        ?TCallable $callmap_callable,
        TemplateResult $template_result,
        Context $context
    ) : Type\Union {
        $stmt_type = null;
        $config = $codebase->config;

        if ($codebase->functions->return_type_provider->has($function_id)) {
            $stmt_type = $codebase->functions->return_type_provider->getReturnType(
                $statements_analyzer,
                $function_id,
                $stmt->args,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $function_name)
            );
        }

        if (!$stmt_type) {
            if (!$in_call_map || $is_stubbed) {
                if ($function_storage && $function_storage->template_types) {
                    foreach ($function_storage->template_types as $template_name => $_) {
                        if (!isset($template_result->upper_bounds[$template_name])) {
                            if ($template_name === 'TFunctionArgCount') {
                                $template_result->upper_bounds[$template_name] = [
                                    'fn-' . $function_id => new TemplateBound(
                                        Type::getInt(false, count($stmt->args))
                                    )
                                ];
                            } elseif ($template_name === 'TPhpMajorVersion') {
                                $template_result->upper_bounds[$template_name] = [
                                    'fn-' . $function_id => new TemplateBound(
                                        Type::getInt(false, $codebase->php_major_version)
                                    )
                                ];
                            } else {
                                $template_result->upper_bounds[$template_name] = [
                                    'fn-' . $function_id => new TemplateBound(
                                        Type::getEmpty()
                                    )
                                ];
                            }
                        }
                    }
                }

                if ($function_storage && !$context->isSuppressingExceptions($statements_analyzer)) {
                    $context->mergeFunctionExceptions(
                        $function_storage,
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    );
                }

                try {
                    if ($function_storage && $function_storage->return_type) {
                        $return_type = clone $function_storage->return_type;

                        if ($template_result->upper_bounds && $function_storage->template_types) {
                            $return_type = TypeExpander::expandUnion(
                                $codebase,
                                $return_type,
                                null,
                                null,
                                null
                            );

                            TemplateInferredTypeReplacer::replace(
                                $return_type,
                                $template_result,
                                $codebase
                            );
                        }

                        $return_type = TypeExpander::expandUnion(
                            $codebase,
                            $return_type,
                            null,
                            null,
                            null
                        );

                        $return_type_location = $function_storage->return_type_location;

                        $event = new AfterFunctionCallAnalysisEvent(
                            $stmt,
                            $function_id,
                            $context,
                            $statements_analyzer->getSource(),
                            $codebase,
                            $return_type,
                            []
                        );

                        $config->eventDispatcher->dispatchAfterFunctionCallAnalysis($event);
                        $file_manipulations = $event->getFileReplacements();

                        if ($file_manipulations) {
                            FileManipulationBuffer::add(
                                $statements_analyzer->getFilePath(),
                                $file_manipulations
                            );
                        }

                        $stmt_type = $return_type;
                        $return_type->by_ref = $function_storage->returns_by_ref;

                        // only check the type locally if it's defined externally
                        if ($return_type_location &&
                            !$is_stubbed && // makes lookups or array_* functions quicker
                            !$config->isInProjectDirs($return_type_location->file_path)
                        ) {
                            $return_type->check(
                                $statements_analyzer,
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $statements_analyzer->getSuppressedIssues(),
                                $context->phantom_classes,
                                true,
                                false,
                                false,
                                $context->calling_method_id
                            );
                        }
                    }
                } catch (\InvalidArgumentException $e) {
                    // this can happen when the function was defined in the Config startup script
                    $stmt_type = Type::getMixed();
                }
            } else {
                if (!$callmap_callable) {
                    throw new \UnexpectedValueException('We should have a callmap callable here');
                }

                $stmt_type = self::getReturnTypeFromCallMapWithArgs(
                    $statements_analyzer,
                    $function_id,
                    $stmt->args,
                    $callmap_callable,
                    $context
                );
            }
        }

        if (!$stmt_type) {
            $stmt_type = Type::getMixed();
        }

        if (!$statements_analyzer->data_flow_graph instanceof TaintFlowGraph || !$function_storage) {
            return $stmt_type;
        }

        $return_node = self::taintReturnType(
            $statements_analyzer,
            $stmt,
            $function_id,
            $function_storage,
            $stmt_type,
            $template_result
        );

        if ($function_storage->proxy_calls !== null) {
            foreach ($function_storage->proxy_calls as $proxy_call) {
                $fake_call_arguments = [];
                foreach ($proxy_call['params'] as $i) {
                    $fake_call_arguments[] = $stmt->args[$i];
                }

                $fake_call_factory = new BuilderFactory();

                if (strpos($proxy_call['fqn'], '::') !== false) {
                    list($fqcn, $method) = explode('::', $proxy_call['fqn']);
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
        Context $context
    ): Type\Union {
        $call_map_key = strtolower($function_id);

        $codebase = $statements_analyzer->getCodebase();

        if (!$call_args) {
            switch ($call_map_key) {
                case 'hrtime':
                    return new Type\Union([
                        new Type\Atomic\TKeyedArray([
                            Type::getInt(),
                            Type::getInt()
                        ])
                    ]);

                case 'get_called_class':
                    return new Type\Union([
                        new Type\Atomic\TClassString(
                            $context->self ?: 'object',
                            $context->self ? new Type\Atomic\TNamedObject($context->self, true) : null
                        )
                    ]);

                case 'get_parent_class':
                    if ($context->self && $codebase->classExists($context->self)) {
                        $classlike_storage = $codebase->classlike_storage_provider->get($context->self);

                        if ($classlike_storage->parent_classes) {
                            return new Type\Union([
                                new Type\Atomic\TClassString(
                                    \array_values($classlike_storage->parent_classes)[0]
                                )
                            ]);
                        }
                    }
            }
        } else {
            switch ($call_map_key) {
                case 'count':
                    if (($first_arg_type = $statements_analyzer->node_data->getType($call_args[0]->value))) {
                        $atomic_types = $first_arg_type->getAtomicTypes();

                        if (count($atomic_types) === 1) {
                            if (isset($atomic_types['array'])) {
                                if ($atomic_types['array'] instanceof Type\Atomic\TCallableArray
                                    || $atomic_types['array'] instanceof Type\Atomic\TCallableList
                                    || $atomic_types['array'] instanceof Type\Atomic\TCallableKeyedArray
                                ) {
                                    return Type::getInt(false, 2);
                                }

                                if ($atomic_types['array'] instanceof Type\Atomic\TNonEmptyArray) {
                                    return new Type\Union([
                                        $atomic_types['array']->count !== null
                                            ? new Type\Atomic\TLiteralInt($atomic_types['array']->count)
                                            : new Type\Atomic\TPositiveInt
                                    ]);
                                }

                                if ($atomic_types['array'] instanceof Type\Atomic\TNonEmptyList) {
                                    return new Type\Union([
                                        $atomic_types['array']->count !== null
                                            ? new Type\Atomic\TLiteralInt($atomic_types['array']->count)
                                            : new Type\Atomic\TPositiveInt
                                    ]);
                                }

                                if ($atomic_types['array'] instanceof Type\Atomic\TKeyedArray
                                    && $atomic_types['array']->sealed
                                ) {
                                    return new Type\Union([
                                        new Type\Atomic\TLiteralInt(count($atomic_types['array']->properties))
                                    ]);
                                }

                                return new Type\Union([
                                    new Type\Atomic\TLiteralInt(0),
                                    new Type\Atomic\TPositiveInt
                                ]);
                            }
                        }
                    }

                    break;

                case 'hrtime':
                    if (($first_arg_type = $statements_analyzer->node_data->getType($call_args[0]->value))) {
                        if ((string) $first_arg_type === 'true') {
                            $int = Type::getInt();
                            $int->from_calculation = true;
                            return $int;
                        }

                        if ((string) $first_arg_type === 'false') {
                            return new Type\Union([
                                new Type\Atomic\TKeyedArray([
                                    Type::getInt(),
                                    Type::getInt()
                                ])
                            ]);
                        }

                        return new Type\Union([
                            new Type\Atomic\TKeyedArray([
                                Type::getInt(),
                                Type::getInt()
                            ]),
                            new Type\Atomic\TInt()
                        ]);
                    }

                    $int = Type::getInt();
                    $int->from_calculation = true;
                    return $int;

                case 'min':
                case 'max':
                    if (isset($call_args[0])) {
                        $first_arg = $call_args[0]->value;

                        if ($first_arg_type = $statements_analyzer->node_data->getType($first_arg)) {
                            if ($first_arg_type->hasArray()) {
                                /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
                                $array_type = $first_arg_type->getAtomicTypes()['array'];
                                if ($array_type instanceof Type\Atomic\TKeyedArray) {
                                    return $array_type->getGenericValueType();
                                }

                                if ($array_type instanceof Type\Atomic\TArray) {
                                    return clone $array_type->type_params[1];
                                }

                                if ($array_type instanceof Type\Atomic\TList) {
                                    return clone $array_type->type_param;
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
                    $string_type = Type::getString();
                    $string_type->addType(new Type\Atomic\TNull);
                    $string_type->ignore_nullable_issues = true;

                    $call_map_return_type = new Type\Union([
                        new Type\Atomic\TNonEmptyList(
                            $string_type
                        ),
                        new Type\Atomic\TFalse,
                        new Type\Atomic\TNull
                    ]);

                    if ($codebase->config->ignore_internal_nullable_issues) {
                        $call_map_return_type->ignore_nullable_issues = true;
                    }

                    if ($codebase->config->ignore_internal_falsable_issues) {
                        $call_map_return_type->ignore_falsable_issues = true;
                    }

                    return $call_map_return_type;
            }
        }

        $stmt_type = $callmap_callable->return_type
            ? clone $callmap_callable->return_type
            : Type::getMixed();

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
                    $stmt_type->ignore_falsable_issues = true;
                }
        }

        switch ($call_map_key) {
            case 'array_replace':
            case 'array_replace_recursive':
                if ($codebase->config->ignore_internal_nullable_issues) {
                    $stmt_type->ignore_nullable_issues = true;
                }
                break;
        }

        return $stmt_type;
    }

    private static function taintReturnType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        string $function_id,
        FunctionLikeStorage $function_storage,
        Type\Union $stmt_type,
        TemplateResult $template_result
    ) : ?DataFlowNode {
        if (!$statements_analyzer->data_flow_graph instanceof TaintFlowGraph
            || \in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
        ) {
            return null;
        }

        $node_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

        $function_call_node = DataFlowNode::getForMethodReturn(
            $function_id,
            $function_id,
            $function_storage->signature_return_type_location ?: $function_storage->location,
            $function_storage->specialize_call ? $node_location : null
        );

        $statements_analyzer->data_flow_graph->addNode($function_call_node);

        $codebase = $statements_analyzer->getCodebase();

        $conditionally_removed_taints = [];

        foreach ($function_storage->conditionally_removed_taints as $conditionally_removed_taint) {
            $conditionally_removed_taint = clone $conditionally_removed_taint;

            TemplateInferredTypeReplacer::replace(
                $conditionally_removed_taint,
                $template_result,
                $codebase
            );

            $expanded_type = TypeExpander::expandUnion(
                $statements_analyzer->getCodebase(),
                $conditionally_removed_taint,
                null,
                null,
                null,
                true,
                true
            );

            if (!$expanded_type->isNullable()) {
                foreach ($expanded_type->getLiteralStrings() as $literal_string) {
                    $conditionally_removed_taints[] = $literal_string->value;
                }
            }
        }

        if ($conditionally_removed_taints && $function_storage->location) {
            $assignment_node = DataFlowNode::getForAssignment(
                $function_id . '-escaped',
                $function_storage->signature_return_type_location ?: $function_storage->location,
                $function_call_node->specialization_key
            );

            $statements_analyzer->data_flow_graph->addPath(
                $function_call_node,
                $assignment_node,
                'conditionally-escaped',
                [],
                $conditionally_removed_taints
            );

            $stmt_type->parent_nodes[$assignment_node->id] = $assignment_node;
        } else {
            $stmt_type->parent_nodes[$function_call_node->id] = $function_call_node;
        }

        if ($function_storage->return_source_params) {
            $removed_taints = $function_storage->removed_taints;

            if ($function_id === 'preg_replace' && count($stmt->args) > 2) {
                $first_stmt_type = $statements_analyzer->node_data->getType($stmt->args[0]->value);
                $second_stmt_type = $statements_analyzer->node_data->getType($stmt->args[1]->value);

                if ($first_stmt_type
                    && $second_stmt_type
                    && $first_stmt_type->isSingleStringLiteral()
                    && $second_stmt_type->isSingleStringLiteral()
                ) {
                    $first_arg_value = $first_stmt_type->getSingleStringLiteral()->value;

                    $pattern = \substr($first_arg_value, 1, -1);

                    if ($pattern[0] === '['
                        && $pattern[1] === '^'
                        && \substr($pattern, -1) === ']'
                    ) {
                        $pattern = \substr($pattern, 2, -1);

                        if (self::simpleExclusion($pattern, $first_arg_value[0])) {
                            $removed_taints[] = 'html';
                            $removed_taints[] = 'sql';
                        }
                    }
                }
            }

            foreach ($function_storage->return_source_params as $i => $path_type) {
                if (!isset($stmt->args[$i])) {
                    continue;
                }

                $current_arg_is_variadic = $function_storage->params[$i]->is_variadic;
                $taintableArgIndex = [$i];

                if ($current_arg_is_variadic) {
                    $max_params = count($stmt->args) - 1;
                    for ($arg_index = $i + 1; $arg_index <= $max_params; $arg_index++) {
                        $taintableArgIndex[] = $arg_index;
                    }
                }

                foreach ($taintableArgIndex as $argIndex) {
                    $arg_location = new CodeLocation(
                        $statements_analyzer->getSource(),
                        $stmt->args[$argIndex]->value
                    );

                    $function_param_sink = DataFlowNode::getForMethodArgument(
                        $function_id,
                        $function_id,
                        $argIndex,
                        $arg_location,
                        $function_storage->specialize_call ? $node_location : null
                    );

                    $statements_analyzer->data_flow_graph->addNode($function_param_sink);

                    $statements_analyzer->data_flow_graph->addPath(
                        $function_param_sink,
                        $function_call_node,
                        $path_type,
                        $function_storage->added_taints,
                        $removed_taints
                    );
                }
            }
        }

        if ($function_storage->taint_source_types) {
            $method_node = TaintSource::getForMethodReturn(
                $function_id,
                $function_id,
                $node_location
            );

            $method_node->taints = $function_storage->taint_source_types;

            $statements_analyzer->data_flow_graph->addSource($method_node);
        }

        return $function_call_node;
    }

    /**
     * @psalm-pure
     */
    private static function simpleExclusion(string $pattern, string $escape_char) : bool
    {
        $str_length = \strlen($pattern);

        for ($i = 0; $i < $str_length; $i++) {
            $current = $pattern[$i];
            $next = $pattern[$i + 1] ?? null;

            if ($current === '\\') {
                if ($next == null
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
