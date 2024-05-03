<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CastAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Codebase\ConstantTypeResolver;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\ArgumentTypeCoercion;
use Psalm\Issue\DeprecatedConstant;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidLiteralArgument;
use Psalm\Issue\InvalidScalarArgument;
use Psalm\Issue\MixedArgument;
use Psalm\Issue\MixedArgumentTypeCoercion;
use Psalm\Issue\NamedArgumentNotAllowed;
use Psalm\Issue\NoValue;
use Psalm\Issue\NullArgument;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\PossiblyFalseArgument;
use Psalm\Issue\PossiblyInvalidArgument;
use Psalm\Issue\PossiblyNullArgument;
use Psalm\IssueBuffer;
use Psalm\Node\VirtualArg;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_filter;
use function count;
use function explode;
use function implode;
use function in_array;
use function ord;
use function preg_split;
use function reset;
use function strpos;
use function strtolower;
use function substr;
use function substr_count;

use const DIRECTORY_SEPARATOR;
use const PREG_SPLIT_NO_EMPTY;

/**
 * @internal
 */
final class ArgumentAnalyzer
{
    /**
     * @param  array<string, array<string, Union>> $class_generic_params
     * @return false|null
     */
    public static function checkArgumentMatches(
        StatementsAnalyzer $statements_analyzer,
        ?string $cased_method_id,
        ?MethodIdentifier $method_id,
        ?string $self_fq_class_name,
        ?string $static_fq_class_name,
        CodeLocation $function_call_location,
        ?FunctionLikeParameter $function_param,
        int $argument_offset,
        int $unpacked_argument_offset,
        bool $allow_named_args,
        PhpParser\Node\Arg $arg,
        ?Union $arg_value_type,
        Context $context,
        array $class_generic_params,
        ?TemplateResult $template_result,
        bool $specialize_taint,
        bool $in_call_map
    ): ?bool {
        $codebase = $statements_analyzer->getCodebase();

        if (!$arg_value_type) {
            if ($function_param && !$function_param->by_ref) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                $param_type = $function_param->type;

                if ($function_param->is_variadic
                    && $param_type
                    && $param_type->hasArray()
                ) {
                    $array_type = $param_type->getArray();

                    if ($array_type instanceof TKeyedArray && $array_type->is_list) {
                        $param_type = $array_type->getGenericValueType();
                    } elseif ($array_type instanceof TArray) {
                        $param_type = $array_type->type_params[1];
                    }
                }

                if ($param_type && !$param_type->hasMixed()) {
                    IssueBuffer::maybeAdd(
                        new MixedArgument(
                            'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id
                                . ' cannot be mixed, expecting ' . $param_type,
                            new CodeLocation($statements_analyzer->getSource(), $arg->value),
                            $cased_method_id,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            }

            return null;
        }

        if (!$function_param) {
            return null;
        }

        if ($function_param->expect_variable
            && $arg_value_type->isSingleStringLiteral()
            && !$arg->value instanceof PhpParser\Node\Scalar\MagicConst
            && !$arg->value instanceof PhpParser\Node\Expr\ConstFetch
            && !$arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
        ) {
            $values = preg_split('//u', $arg_value_type->getSingleStringLiteral()->value, -1, PREG_SPLIT_NO_EMPTY);

            if ($values !== false) {
                $prev_ord = 0;

                $gt_count = 0;

                foreach ($values as $value) {
                    $ord = ord($value);

                    if ($ord > $prev_ord) {
                        $gt_count++;
                    }

                    $prev_ord = $ord;
                }

                if (substr_count($arg_value_type->getSingleStringLiteral()->value, DIRECTORY_SEPARATOR) <= 2
                    && (count($values) < 12 || ($gt_count / count($values)) < 0.8)
                ) {
                    IssueBuffer::maybeAdd(
                        new InvalidLiteralArgument(
                            'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id
                                . ' expects a non-literal value, but ' . $arg_value_type->getId() . ' provided',
                            new CodeLocation($statements_analyzer->getSource(), $arg->value),
                            $cased_method_id,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            }
        }

        if (self::checkFunctionLikeTypeMatches(
            $statements_analyzer,
            $codebase,
            $cased_method_id,
            $method_id,
            $self_fq_class_name,
            $static_fq_class_name,
            $function_call_location,
            $function_param,
            $allow_named_args,
            $arg_value_type,
            $argument_offset,
            $unpacked_argument_offset,
            $arg,
            $context,
            $class_generic_params,
            $template_result,
            $specialize_taint,
            $in_call_map,
        ) === false) {
            return false;
        }

        return null;
    }

    /**
     * @param  array<string, array<string, Union>> $class_generic_params
     * @return false|null
     */
    private static function checkFunctionLikeTypeMatches(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        ?string $cased_method_id,
        ?MethodIdentifier $method_id,
        ?string $self_fq_class_name,
        ?string $static_fq_class_name,
        CodeLocation $function_call_location,
        FunctionLikeParameter $function_param,
        bool $allow_named_args,
        Union $arg_value_type,
        int $argument_offset,
        int $unpacked_argument_offset,
        PhpParser\Node\Arg $arg,
        Context $context,
        ?array $class_generic_params,
        ?TemplateResult $template_result,
        bool $specialize_taint,
        bool $in_call_map
    ): ?bool {
        if (!$function_param->type) {
            if (!$codebase->infer_types_from_usage && !$statements_analyzer->data_flow_graph) {
                return null;
            }

            $param_type = Type::getMixed();
        } else {
            $param_type = $function_param->type;
        }

        $bindable_template_params = [];

        if ($template_result) {
            $bindable_template_params = $param_type->getTemplateTypes();
        }

        $parent_class = null;

        $classlike_storage = null;
        $static_classlike_storage = null;

        if ($self_fq_class_name) {
            $classlike_storage = $codebase->classlike_storage_provider->get($self_fq_class_name);
            $parent_class = $classlike_storage->parent_class;
            $static_classlike_storage = $classlike_storage;

            if ($static_fq_class_name && $static_fq_class_name !== $self_fq_class_name) {
                $static_classlike_storage = $codebase->classlike_storage_provider->get($static_fq_class_name);
            }
        }

        $param_type = TypeExpander::expandUnion(
            $codebase,
            $param_type,
            $classlike_storage->name ?? null,
            $static_classlike_storage->name ?? null,
            $parent_class,
            true,
            false,
            $static_classlike_storage->final ?? false,
            true,
        );

        if ($class_generic_params) {
            // here we're replacing the param types and arg types with the bound
            // class template params.
            //
            // For example, if we're operating on a class Foo with params TKey and TValue,
            // and we're calling a method "add(TKey $key, TValue $value)" on an instance
            // of that class where we know that TKey is int and TValue is string, then we
            // want to substitute the expected parameters so it's as if we were actually
            // calling "add(int $key, string $value)"
            $readonly_template_result = new TemplateResult($class_generic_params, []);

            // This flag ensures that the template results will never be written to
            // It also supercedes the `$add_lower_bounds` flag so that closure params
            // don’t get overwritten
            $readonly_template_result->readonly = true;

            $param_type = TemplateStandinTypeReplacer::replace(
                $param_type,
                $readonly_template_result,
                $codebase,
                $statements_analyzer,
                $arg_value_type,
                $argument_offset,
                $context->self,
                $context->calling_function_id ?: $context->calling_method_id,
            );

            $arg_value_type = TemplateStandinTypeReplacer::replace(
                $arg_value_type,
                $readonly_template_result,
                $codebase,
                $statements_analyzer,
                $arg_value_type,
                $argument_offset,
                $context->self,
                $context->calling_function_id ?: $context->calling_method_id,
            );
        }

        if ($template_result && $template_result->template_types) {
            $arg_type_param = $arg_value_type;

            if ($arg->unpack) {
                $arg_type_param = null;

                foreach ($arg_value_type->getAtomicTypes() as $arg_atomic_type) {
                    if ($arg_atomic_type instanceof TList) {
                        $arg_atomic_type = $arg_atomic_type->getKeyedArray();
                    }

                    if ($arg_atomic_type instanceof TArray
                        || $arg_atomic_type instanceof TKeyedArray
                    ) {
                        if ($arg_atomic_type instanceof TKeyedArray) {
                            $arg_type_param = $arg_atomic_type->getGenericValueType();
                        } else {
                            $arg_type_param = $arg_atomic_type->type_params[1];
                        }
                    } elseif ($arg_atomic_type instanceof TIterable) {
                        $arg_type_param = $arg_atomic_type->type_params[1];
                    } elseif ($arg_atomic_type instanceof TNamedObject) {
                        ForeachAnalyzer::getKeyValueParamsForTraversableObject(
                            $arg_atomic_type,
                            $codebase,
                            $key_type,
                            $arg_type_param,
                        );
                    }
                }

                if (!$arg_type_param) {
                    $arg_type_param = new Union([
                        new TMixed(),
                    ], ['parent_nodes' => $arg_value_type->parent_nodes]);
                }
            }

            $param_type = TemplateStandinTypeReplacer::replace(
                $param_type,
                $template_result,
                $codebase,
                $statements_analyzer,
                $arg_type_param,
                $argument_offset,
                !$statements_analyzer->isStatic()
                    && (!$method_id || $method_id->method_name !== '__construct')
                    ? $context->self
                    : null,
                $context->calling_method_id ?: $context->calling_function_id,
            );

            foreach ($bindable_template_params as $template_type) {
                if (!isset(
                    $template_result->lower_bounds
                        [$template_type->param_name]
                        [$template_type->defining_class],
                )) {
                    if (isset(
                        $template_result->upper_bounds
                            [$template_type->param_name]
                            [$template_type->defining_class],
                    )) {
                        $template_result->lower_bounds[$template_type->param_name][$template_type->defining_class] = [
                            new TemplateBound(
                                $template_result->upper_bounds
                                    [$template_type->param_name]
                                    [$template_type->defining_class]->type,
                            ),
                        ];
                    } else {
                        $template_result->lower_bounds[$template_type->param_name][$template_type->defining_class] = [
                            new TemplateBound(
                                $template_type->as,
                            ),
                        ];
                    }
                }
            }

            $param_type = TypeExpander::expandUnion(
                $codebase,
                $param_type,
                $classlike_storage->name ?? null,
                $static_classlike_storage->name ?? null,
                $parent_class,
                true,
                false,
                $static_classlike_storage->final ?? false,
                true,
            );
        }

        $fleshed_out_signature_type = $function_param->signature_type
            ? TypeExpander::expandUnion(
                $codebase,
                $function_param->signature_type,
                $classlike_storage->name ?? null,
                $static_classlike_storage->name ?? null,
                $parent_class,
            )
            : null;

        $unpacked_atomic_array = null;

        if ($arg->unpack) {
            if ($arg_value_type->hasMixed()) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                IssueBuffer::maybeAdd(
                    new MixedArgument(
                        'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id
                            . ' cannot unpack ' . $arg_value_type->getId() . ', expecting iterable',
                        new CodeLocation($statements_analyzer->getSource(), $arg->value),
                        $cased_method_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );

                if ($cased_method_id) {
                    $arg_location = new CodeLocation($statements_analyzer->getSource(), $arg->value);

                    self::processTaintedness(
                        $statements_analyzer,
                        $cased_method_id,
                        $method_id,
                        $argument_offset,
                        $arg_location,
                        $function_call_location,
                        $function_param,
                        $arg_value_type,
                        $arg->value,
                        $context,
                        $specialize_taint,
                    );
                }

                return null;
            }

            if ($arg_value_type->hasArray()) {
                $unpacked_atomic_array = $arg_value_type->getArray();
                $arg_key_allowed = true;

                if ($unpacked_atomic_array instanceof TKeyedArray) {
                    if (!$allow_named_args && !$unpacked_atomic_array->getGenericKeyType()->isInt()) {
                        $arg_key_allowed = false;
                    }

                    if ($function_param->is_variadic) {
                        $arg_value_type = $unpacked_atomic_array->getGenericValueType();
                    } elseif ($codebase->analysis_php_version_id >= 8_00_00
                        && $allow_named_args
                        && isset($unpacked_atomic_array->properties[$function_param->name])
                    ) {
                        $arg_value_type = $unpacked_atomic_array->properties[$function_param->name];
                    } elseif ($unpacked_atomic_array->is_list
                        && isset($unpacked_atomic_array->properties[$unpacked_argument_offset])
                    ) {
                        $arg_value_type = $unpacked_atomic_array->properties[$unpacked_argument_offset];
                    } elseif ($unpacked_atomic_array->fallback_params) {
                        $arg_value_type = $unpacked_atomic_array->fallback_params[1];
                    } elseif ($function_param->is_optional && $function_param->default_type) {
                        if ($function_param->default_type instanceof Union) {
                            $arg_value_type = $function_param->default_type;
                        } else {
                            $arg_value_type_atomic = ConstantTypeResolver::resolve(
                                $codebase->classlikes,
                                $function_param->default_type,
                                $statements_analyzer,
                            );

                            $arg_value_type = new Union([$arg_value_type_atomic]);
                        }
                    } else {
                        $arg_value_type = Type::getMixed();
                    }
                } elseif ($unpacked_atomic_array instanceof TClassStringMap) {
                    $arg_value_type = Type::getMixed();
                } else {
                    if (!$allow_named_args && !$unpacked_atomic_array->type_params[0]->isInt()) {
                        $arg_key_allowed = false;
                    }
                    $arg_value_type = $unpacked_atomic_array->type_params[1];
                }

                if (!$arg_key_allowed) {
                    IssueBuffer::maybeAdd(
                        new NamedArgumentNotAllowed(
                            'Method ' . $cased_method_id
                                . ' called with named unpacked array ' . $unpacked_atomic_array->getId()
                                . ' (array with string keys)',
                            new CodeLocation($statements_analyzer->getSource(), $arg->value),
                            $cased_method_id,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            } else {
                $non_iterable = false;
                $invalid_key = false;
                $invalid_string_key = false;
                $possibly_matches = false;
                foreach ($arg_value_type->getAtomicTypes() as $atomic_type) {
                    if (!$atomic_type->isIterable($codebase)) {
                        $non_iterable = true;
                    } else {
                        $key_type = $codebase->getKeyValueParamsForTraversableObject($atomic_type)[0];
                        if (!UnionTypeComparator::isContainedBy(
                            $codebase,
                            $key_type,
                            Type::getArrayKey(),
                        )) {
                            $invalid_key = true;

                            continue;
                        }
                        if (($codebase->analysis_php_version_id < 8_00_00 || !$allow_named_args)
                            && !$key_type->isInt()
                        ) {
                            $invalid_string_key = true;

                            continue;
                        }
                        $possibly_matches = true;
                    }
                }

                $issue_type = $possibly_matches ? PossiblyInvalidArgument::class : InvalidArgument::class;
                if ($non_iterable) {
                    IssueBuffer::maybeAdd(
                        new $issue_type(
                            'Tried to unpack non-iterable ' . $arg_value_type->getId(),
                            new CodeLocation($statements_analyzer->getSource(), $arg->value),
                            $cased_method_id,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
                if ($invalid_key) {
                    IssueBuffer::maybeAdd(
                        new $issue_type(
                            'Method ' . $cased_method_id
                                . ' called with unpacked iterable ' . $arg_value_type->getId()
                                . ' with invalid key (must be '
                                . ($codebase->analysis_php_version_id < 8_00_00 ? 'int' : 'int|string') . ')',
                            new CodeLocation($statements_analyzer->getSource(), $arg->value),
                            $cased_method_id,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
                if ($invalid_string_key) {
                    if ($codebase->analysis_php_version_id < 8_00_00) {
                        IssueBuffer::maybeAdd(
                            new $issue_type(
                                'String keys not supported in unpacked arguments',
                                new CodeLocation($statements_analyzer->getSource(), $arg->value),
                                $cased_method_id,
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    } else {
                        IssueBuffer::maybeAdd(
                            new NamedArgumentNotAllowed(
                                'Method ' . $cased_method_id
                                    . ' called with named unpacked iterable ' . $arg_value_type->getId()
                                    . ' (iterable with string keys)',
                                new CodeLocation($statements_analyzer->getSource(), $arg->value),
                                $cased_method_id,
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }
                }

                return null;
            }
        } else {
            if (!$allow_named_args && $arg->name !== null) {
                IssueBuffer::maybeAdd(
                    new NamedArgumentNotAllowed(
                        'Method ' . $cased_method_id. ' called with named argument ' . $arg->name->name,
                        new CodeLocation($statements_analyzer->getSource(), $arg->value),
                        $cased_method_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }

        // bypass verifying argument types when collecting initialisations,
        // because the argument locations are not reliable (file names normally differ)
        // See https://github.com/vimeo/psalm/issues/5662
        if ($arg instanceof VirtualArg
            && $context->collect_initializations
        ) {
            return null;
        }

        if (self::verifyType(
            $statements_analyzer,
            $arg_value_type,
            $param_type,
            $fleshed_out_signature_type,
            $cased_method_id,
            $method_id,
            $argument_offset,
            new CodeLocation($statements_analyzer->getSource(), $arg->value),
            $arg->value,
            $context,
            $function_param,
            $arg->unpack,
            $unpacked_atomic_array,
            $specialize_taint,
            $in_call_map,
            $function_call_location,
        ) === false) {
            return false;
        }

        return null;
    }

    /**
     * @param TKeyedArray|TArray|TClassStringMap|null $unpacked_atomic_array
     * @return  null|false
     */
    public static function verifyType(
        StatementsAnalyzer $statements_analyzer,
        Union $input_type,
        Union $param_type,
        ?Union $signature_param_type,
        ?string $cased_method_id,
        ?MethodIdentifier $method_id,
        int $argument_offset,
        CodeLocation $arg_location,
        PhpParser\Node\Expr $input_expr,
        Context $context,
        FunctionLikeParameter $function_param,
        bool $unpack,
        ?Atomic $unpacked_atomic_array,
        bool $specialize_taint,
        bool $in_call_map,
        CodeLocation $function_call_location
    ): ?bool {
        $codebase = $statements_analyzer->getCodebase();

        if ($param_type->hasMixed()) {
            if ($codebase->infer_types_from_usage
                && !$input_type->hasMixed()
                && !$param_type->from_docblock
                && !$param_type->had_template
                && $method_id
                && strpos($method_id->method_name, '__') !== 0
            ) {
                $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                if ($declaring_method_id) {
                    $id_lc = strtolower((string) $declaring_method_id);
                    $codebase->analyzer->possible_method_param_types[$id_lc][$argument_offset]
                        = Type::combineUnionTypes(
                            $codebase->analyzer->possible_method_param_types[$id_lc][$argument_offset] ?? null,
                            $input_type,
                            $codebase,
                        );
                }
            }

            if ($cased_method_id) {
                self::processTaintedness(
                    $statements_analyzer,
                    $cased_method_id,
                    $method_id,
                    $argument_offset,
                    $arg_location,
                    $function_call_location,
                    $function_param,
                    $input_type,
                    $input_expr,
                    $context,
                    $specialize_taint,
                );
            }

            return null;
        }

        $method_identifier = $cased_method_id ? ' of ' . $cased_method_id : '';

        if ($input_type->hasMixed()) {
            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
            }

            $origin_locations = [];

            if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph) {
                foreach ($input_type->parent_nodes as $parent_node) {
                    $origin_locations = [
                        ...$origin_locations,
                        ...$statements_analyzer->data_flow_graph->getOriginLocations($parent_node),
                    ];
                }
            }

            $origin_location = count($origin_locations) === 1 ? reset($origin_locations) : null;

            if ($origin_location && $origin_location->getHash() === $arg_location->getHash()) {
                $origin_location = null;
            }

            IssueBuffer::maybeAdd(
                new MixedArgument(
                    'Argument ' . ($argument_offset + 1) . $method_identifier
                        . ' cannot be ' . $input_type->getId() . ', expecting ' .
                        $param_type,
                    $arg_location,
                    $cased_method_id,
                    $origin_location,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );

            if ($input_type->isMixed()) {
                if (!$function_param->by_ref
                    && !($function_param->is_variadic xor $unpack)
                    && $cased_method_id !== 'echo'
                    && $cased_method_id !== 'print'
                    && (!$in_call_map || $context->strict_types)
                ) {
                    self::coerceValueAfterGatekeeperArgument(
                        $statements_analyzer,
                        $input_type,
                        false,
                        $input_expr,
                        $param_type,
                        $signature_param_type,
                        $context,
                        $unpack,
                        $unpacked_atomic_array,
                    );
                }
            }

            if ($cased_method_id) {
                self::processTaintedness(
                    $statements_analyzer,
                    $cased_method_id,
                    $method_id,
                    $argument_offset,
                    $arg_location,
                    $function_call_location,
                    $function_param,
                    $input_type,
                    $input_expr,
                    $context,
                    $specialize_taint,
                );
            }

            if ($input_type->isMixed()) {
                return null;
            }
        }

        if ($input_type->isNever()) {
            if (!IssueBuffer::accepts(
                new NoValue(
                    'All possible types for this argument were invalidated - This may be dead code',
                    $arg_location,
                ),
                $statements_analyzer->getSuppressedIssues(),
            )) {
                // if the error is suppressed, do not treat it as exited anymore
                $context->has_returned = false;
            }

            return null;
        }

        if (!$context->collect_initializations
            && !$context->collect_mutations
            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
            && (!(($parent_source = $statements_analyzer->getSource())
                    instanceof FunctionLikeAnalyzer)
                || !$parent_source->getSource() instanceof TraitAnalyzer)
        ) {
            $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
        }

        if ($function_param->by_ref || $function_param->is_optional) {
            //if the param is optional or a ref, we'll allow the input to be possibly_undefined
            $param_type = $param_type->setPossiblyUndefined(true);
        }

        if ($param_type->hasCallableType() && $param_type->isSingle()) {
            // we do this replacement early because later we don't have access to the
            // $statements_analyzer, which is necessary to understand string function names
            $input_type = $input_type->getBuilder();
            foreach ($input_type->getAtomicTypes() as $key => $atomic_type) {
                $container_callable_type = $param_type->getSingleAtomic();
                $container_callable_type = $container_callable_type instanceof TCallable
                    ? $container_callable_type
                    : null;

                $candidate_callable = CallableTypeComparator::getCallableFromAtomic(
                    $codebase,
                    $atomic_type,
                    $container_callable_type,
                    $statements_analyzer,
                    true,
                );

                if ($candidate_callable && $candidate_callable !== $atomic_type) {
                    // if we had an array callable, mark it as used now, since it's not possible later
                    $potential_method_id = null;
                    if ($atomic_type instanceof TList) {
                        $atomic_type = $atomic_type->getKeyedArray();
                    }

                    if ($atomic_type instanceof TKeyedArray) {
                        $potential_method_id = CallableTypeComparator::getCallableMethodIdFromTKeyedArray(
                            $atomic_type,
                            $codebase,
                            $context->calling_method_id,
                            $statements_analyzer->getFilePath(),
                        );
                    } elseif ($atomic_type instanceof TLiteralString
                              && strpos($atomic_type->value, '::')
                    ) {
                        $parts = explode('::', $atomic_type->value);
                        $potential_method_id = new MethodIdentifier(
                            $parts[0],
                            strtolower($parts[1]),
                        );
                    }

                    if ($potential_method_id && $potential_method_id !== 'not-callable') {
                        $codebase->methods->methodExists(
                            $potential_method_id,
                            $context->calling_method_id,
                            $arg_location,
                            $statements_analyzer,
                            $statements_analyzer->getFilePath(),
                            true,
                            $context->insideUse(),
                        );

                        if (self::verifyCallableInContext(
                            $potential_method_id,
                            $cased_method_id,
                            $method_id,
                            $atomic_type,
                            $argument_offset,
                            $arg_location,
                            $context,
                            $codebase,
                            $statements_analyzer,
                        ) === false) {
                            continue;
                        }
                    }

                    $input_type->removeType($key);
                    $input_type->addType($candidate_callable);
                }
            }
            $input_type = $input_type->freeze();
        }

        $union_comparison_results = new TypeComparisonResult();

        $type_match_found = UnionTypeComparator::isContainedBy(
            $codebase,
            $input_type,
            $param_type,
            true,
            !isset($param_type->getAtomicTypes()['true']),
            $union_comparison_results,
        );

        $replace_input_type = false;

        if ($union_comparison_results->replacement_union_type) {
            $replace_input_type = true;
            $input_type = $union_comparison_results->replacement_union_type;
        }

        if ($cased_method_id) {
            self::processTaintedness(
                $statements_analyzer,
                $cased_method_id,
                $method_id,
                $argument_offset,
                $arg_location,
                $function_call_location,
                $function_param,
                $input_type,
                $input_expr,
                $context,
                $specialize_taint,
            );

            if ($function_param->assert_untainted) {
                $input_type = $input_type->setParentNodes([]);
                $replace_input_type = true;
            }
        }

        if ($type_match_found
            && $param_type->hasCallableType()
        ) {
            $potential_method_ids = [];

            $param_types_without_callable = array_filter(
                $param_type->getAtomicTypes(),
                static fn(Atomic $atomic) => !$atomic instanceof Atomic\TCallableInterface,
            );
            $param_type_without_callable = [] !== $param_types_without_callable
                ? new Union($param_types_without_callable)
                : null;

            foreach ($input_type->getAtomicTypes() as $input_type_part) {
                if ($input_type_part instanceof TList) {
                    $input_type_part = $input_type_part->getKeyedArray();
                }

                if ($input_type_part instanceof TKeyedArray) {
                    // If the param accept an array, we don't report arrays as wrong callbacks.
                    if (null !== $param_type_without_callable && UnionTypeComparator::isContainedBy(
                        $codebase,
                        $input_type,
                        $param_type_without_callable,
                    )) {
                        continue;
                    }

                    $potential_method_id = CallableTypeComparator::getCallableMethodIdFromTKeyedArray(
                        $input_type_part,
                        $codebase,
                        $context->calling_method_id,
                        $statements_analyzer->getFilePath(),
                    );

                    if ($potential_method_id === null && $codebase->analysis_php_version_id >= 8_02_00) {
                        [$lhs,] = $input_type_part->properties;
                        if ($lhs->isSingleStringLiteral()
                            && in_array(
                                strtolower($lhs->getSingleStringLiteral()->value),
                                ['self', 'parent', 'static'],
                                true,
                            )) {
                            IssueBuffer::maybeAdd(
                                new DeprecatedConstant(
                                    'Use of "' . $lhs->getSingleStringLiteral()->value . '" in callables is deprecated',
                                    $arg_location,
                                ),
                                $statements_analyzer->getSuppressedIssues(),
                            );
                        }
                    }

                    if ($potential_method_id && $potential_method_id !== 'not-callable') {
                        if (self::verifyCallableInContext(
                            $potential_method_id,
                            $cased_method_id,
                            $method_id,
                            $input_type_part,
                            $argument_offset,
                            $arg_location,
                            $context,
                            $codebase,
                            $statements_analyzer,
                        ) === false) {
                            continue;
                        }

                        $potential_method_ids[] = $potential_method_id;
                    }
                } elseif ($input_type_part instanceof TLiteralString
                    && strpos($input_type_part->value, '::')
                ) {
                    // If the param also accept a string, we don't report string as wrong callbacks.
                    if (null !== $param_type_without_callable && UnionTypeComparator::isContainedBy(
                        $codebase,
                        $input_type,
                        $param_type_without_callable,
                    )) {
                        continue;
                    }

                    $parts = explode('::', $input_type_part->value);
                    /** @psalm-suppress PossiblyUndefinedIntArrayOffset */
                    $potential_method_id = new MethodIdentifier(
                        $parts[0],
                        strtolower($parts[1]),
                    );

                    if ($codebase->analysis_php_version_id >= 8_02_00
                        && in_array(
                            strtolower($potential_method_id->fq_class_name),
                            ['self', 'parent', 'static'],
                            true,
                        )) {
                        IssueBuffer::maybeAdd(
                            new DeprecatedConstant(
                                'Use of "' . $potential_method_id->fq_class_name . '" in callables is deprecated',
                                $arg_location,
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }

                    if (self::verifyCallableInContext(
                        $potential_method_id,
                        $cased_method_id,
                        $method_id,
                        $input_type_part,
                        $argument_offset,
                        $arg_location,
                        $context,
                        $codebase,
                        $statements_analyzer,
                    ) === false) {
                        continue;
                    }

                    $potential_method_ids[] = $potential_method_id;
                }
            }

            foreach ($potential_method_ids as $potential_method_id) {
                $codebase->methods->methodExists(
                    $potential_method_id,
                    $context->calling_method_id,
                    $arg_location,
                    $statements_analyzer,
                    $statements_analyzer->getFilePath(),
                    true,
                    $context->insideUse(),
                );
            }
        }

        if ($context->strict_types
            && !$input_type->hasArray()
            && !$param_type->from_docblock
            && $cased_method_id !== 'echo'
            && $cased_method_id !== 'print'
            && $cased_method_id !== 'sprintf'
        ) {
            $union_comparison_results->scalar_type_match_found = false;

            if ($union_comparison_results->to_string_cast) {
                $union_comparison_results->to_string_cast = false;
                $type_match_found = false;
            }
        }

        if ($union_comparison_results->type_coerced && !$input_type->hasMixed()) {
            if ($union_comparison_results->type_coerced_from_mixed) {
                $origin_locations = [];

                if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph) {
                    foreach ($input_type->parent_nodes as $parent_node) {
                        $origin_locations = [
                            ...$origin_locations,
                            ...$statements_analyzer->data_flow_graph->getOriginLocations($parent_node),
                        ];
                    }
                }

                $origin_location = count($origin_locations) === 1 ? reset($origin_locations) : null;

                if ($origin_location && $origin_location->getHash() === $arg_location->getHash()) {
                    $origin_location = null;
                }

                IssueBuffer::maybeAdd(
                    new MixedArgumentTypeCoercion(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type->getId() .
                            ', but parent type ' . $input_type->getId() . ' provided',
                        $arg_location,
                        $cased_method_id,
                        $origin_location,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            } elseif ($cased_method_id !== 'echo' && $cased_method_id !== 'print') {
                IssueBuffer::maybeAdd(
                    new ArgumentTypeCoercion(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type->getId() .
                            ', but parent type ' . $input_type->getId() . ' provided',
                        $arg_location,
                        $cased_method_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }

        if ($union_comparison_results->to_string_cast && $cased_method_id !== 'echo' && $cased_method_id !== 'print') {
            IssueBuffer::maybeAdd(
                new ImplicitToStringCast(
                    'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' .
                        $param_type->getId() . ', but ' . $input_type->getId() . ' provided with a __toString method',
                    $arg_location,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if (!$type_match_found && !$union_comparison_results->type_coerced) {
            $types_can_be_identical = UnionTypeComparator::canBeContainedBy(
                $codebase,
                $input_type,
                $param_type,
                true,
                true,
            );

            $type = ($input_type->possibly_undefined ? 'possibly undefined ' : '') . $input_type->getId();
            if ($union_comparison_results->scalar_type_match_found) {
                if ($cased_method_id !== 'echo' && $cased_method_id !== 'print') {
                    IssueBuffer::maybeAdd(
                        new InvalidScalarArgument(
                            'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' .
                                $param_type->getId() . ', but ' . $type . ' provided',
                            $arg_location,
                            $cased_method_id,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            } elseif ($types_can_be_identical) {
                IssueBuffer::maybeAdd(
                    new PossiblyInvalidArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type->getId() .
                            ', but possibly different type ' . $type . ' provided',
                        $arg_location,
                        $cased_method_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            } else {
                IssueBuffer::maybeAdd(
                    new InvalidArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type->getId()
                            . ', but ' . $type
                            . ($union_comparison_results->missing_shape_fields
                                ? ' with additional array shape fields ('
                                    . implode(', ', $union_comparison_results->missing_shape_fields)
                                    . ') was'
                                : '')
                            . ' provided',
                        $arg_location,
                        $cased_method_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            return null;
        }

        if ($input_expr instanceof PhpParser\Node\Scalar\String_
            || $input_expr instanceof PhpParser\Node\Expr\Array_
            || $input_expr instanceof PhpParser\Node\Expr\BinaryOp\Concat
        ) {
            self::verifyExplicitParam(
                $statements_analyzer,
                $param_type,
                $arg_location,
                $input_expr,
                $context,
            );

            return null;
        }

        if (!$param_type->isNullable() && $cased_method_id !== 'echo' && $cased_method_id !== 'print') {
            if ($input_type->isNull()) {
                IssueBuffer::maybeAdd(
                    new NullArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be null, ' .
                            'null value provided to parameter with type ' . $param_type->getId(),
                        $arg_location,
                        $cased_method_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );

                return null;
            }

            if ($input_type->isNullable() && !$input_type->ignore_nullable_issues) {
                IssueBuffer::maybeAdd(
                    new PossiblyNullArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be null, possibly ' .
                            'null value provided',
                        $arg_location,
                        $cased_method_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }

        if (!$param_type->isFalsable() &&
            !$param_type->hasBool() &&
            !$param_type->hasScalar() &&
            $cased_method_id !== 'echo' &&
            $cased_method_id !== 'print'
        ) {
            if ($input_type->isFalse()) {
                IssueBuffer::maybeAdd(
                    new InvalidArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be false, ' .
                        $param_type->getId() . ' value expected',
                        $arg_location,
                        $cased_method_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );

                return null;
            }

            if ($input_type->isFalsable() && !$input_type->ignore_falsable_issues) {
                IssueBuffer::maybeAdd(
                    new PossiblyFalseArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be false, possibly ' .
                        $param_type->getId() . ' value expected',
                        $arg_location,
                        $cased_method_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }

        if (($type_match_found || $input_type->hasMixed())
            && !$function_param->by_ref
            && !($function_param->is_variadic xor $unpack)
            && $cased_method_id !== 'echo'
            && $cased_method_id !== 'print'
            && (!$in_call_map || $context->strict_types)
        ) {
            self::coerceValueAfterGatekeeperArgument(
                $statements_analyzer,
                $input_type,
                $replace_input_type,
                $input_expr,
                $param_type,
                $signature_param_type,
                $context,
                $unpack,
                $unpacked_atomic_array,
            );
        }

        return null;
    }

    private static function verifyCallableInContext(
        MethodIdentifier $potential_method_id,
        ?string $cased_method_id,
        ?MethodIdentifier $method_id,
        Atomic $input_type_part,
        int $argument_offset,
        CodeLocation $arg_location,
        Context $context,
        Codebase $codebase,
        StatementsAnalyzer $statements_analyzer
    ): ?bool {
        $method_identifier = $cased_method_id !== null ? ' of ' . $cased_method_id : '';

        if (!$method_id
            || $potential_method_id->fq_class_name !== $context->self
            || $method_id->fq_class_name !== $context->self) {
            if ($input_type_part instanceof TKeyedArray) {
                [$lhs,] = $input_type_part->properties;
            } else {
                $lhs = Type::getString($potential_method_id->fq_class_name);
            }

            try {
                $method_storage = $codebase->methods->getStorage($potential_method_id);

                $lhs_atomic = $lhs->getSingleAtomic();
                if ($lhs->isSingle()
                    && $lhs->hasNamedObjectType()
                    && ($lhs->isStaticObject()
                        || ($lhs_atomic instanceof TNamedObject
                            && !$lhs_atomic->definite_class
                            && $lhs_atomic->value === $context->self))) {
                    // callable $this
                    // some PHP-internal functions (e.g. array_filter) will call the callback within the current context
                    // unlike user-defined functions which call the callback in their context
                    // however this doesn't apply to all
                    // e.g. header_register_callback will not throw an error immediately like user-land functions
                    // however error log "Could not call the sapi_header_callback" if it's not public
                    // this is NOT a complete list, but just what was easily available and to be extended
                    $php_native_non_public_cb = [
                        'array_diff_uassoc',
                        'array_diff_ukey',
                        'array_filter',
                        'array_intersect_uassoc',
                        'array_intersect_ukey',
                        'array_map',
                        'array_reduce',
                        'array_udiff',
                        'array_udiff_assoc',
                        'array_udiff_uassoc',
                        'array_uintersect',
                        'array_uintersect_assoc',
                        'array_uintersect_uassoc',
                        'array_walk',
                        'array_walk_recursive',
                        'preg_replace_callback',
                        'preg_replace_callback_array',
                        'call_user_func',
                        'call_user_func_array',
                        'forward_static_call',
                        'forward_static_call_array',
                        'is_callable',
                        'ob_start',
                        'register_shutdown_function',
                        'register_tick_function',
                        'session_set_save_handler',
                        'set_error_handler',
                        'set_exception_handler',
                        'spl_autoload_register',
                        'spl_autoload_unregister',
                        'uasort',
                        'uksort',
                        'usort',
                    ];

                    if ($potential_method_id->fq_class_name !== $context->self
                        || ($cased_method_id !== null
                            && !$method_id
                            && !in_array($cased_method_id, $php_native_non_public_cb, true))
                        || ($method_id
                            && $method_id->fq_class_name !== $context->self
                            && $method_id->fq_class_name !== 'Closure')
                    ) {
                        if ($method_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PUBLIC) {
                            IssueBuffer::maybeAdd(
                                new InvalidArgument(
                                    'Argument ' . ($argument_offset + 1) . $method_identifier
                                    . ' expects a public callable, but a non-public callable provided',
                                    $arg_location,
                                    $cased_method_id,
                                ),
                                $statements_analyzer->getSuppressedIssues(),
                            );
                            return false;
                        }
                    }
                } elseif ($lhs->isSingle()) {
                    // instance from e.g. new Foo() or static string like Foo::bar
                    if ((!$method_storage->is_static && !$lhs->hasNamedObjectType())
                        || $method_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PUBLIC) {
                        IssueBuffer::maybeAdd(
                            new InvalidArgument(
                                'Argument ' . ($argument_offset + 1) . $method_identifier
                                . ' expects a public static callable, but a '
                                . ($method_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PUBLIC ?
                                    'non-public ' : '')
                                . (!$method_storage->is_static ? 'non-static ' : '')
                                . 'callable provided',
                                $arg_location,
                                $cased_method_id,
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );

                        return false;
                    }
                }
            } catch (UnexpectedValueException $e) {
                // do nothing
            }
        }

        return null;
    }

    /**
     * @param PhpParser\Node\Scalar\String_|PhpParser\Node\Expr\Array_|PhpParser\Node\Expr\BinaryOp\Concat $input_expr
     */
    private static function verifyExplicitParam(
        StatementsAnalyzer $statements_analyzer,
        Union $param_type,
        CodeLocation $arg_location,
        PhpParser\Node\Expr $input_expr,
        Context $context
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        foreach ($param_type->getAtomicTypes() as $param_type_part) {
            if ($param_type_part instanceof TClassString
                && $input_expr instanceof PhpParser\Node\Scalar\String_
                && $param_type->isSingle()
            ) {
                if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $statements_analyzer,
                    $input_expr->value,
                    $arg_location,
                    $context->self,
                    $context->calling_method_id,
                    $statements_analyzer->getSuppressedIssues(),
                    new ClassLikeNameOptions(true),
                ) === false
                ) {
                    return;
                }
            } elseif ($param_type_part instanceof TArray
                && $input_expr instanceof PhpParser\Node\Expr\Array_
            ) {
                foreach ($param_type_part->type_params[1]->getAtomicTypes() as $param_array_type_part) {
                    if ($param_array_type_part instanceof TClassString) {
                        foreach ($input_expr->items as $item) {
                            if ($item && $item->value instanceof PhpParser\Node\Scalar\String_) {
                                if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                                    $statements_analyzer,
                                    $item->value->value,
                                    $arg_location,
                                    $context->self,
                                    $context->calling_method_id,
                                    $statements_analyzer->getSuppressedIssues(),
                                    new ClassLikeNameOptions(true),
                                ) === false
                                ) {
                                    return;
                                }
                            }
                        }
                    }
                }
            } elseif ($param_type_part instanceof TCallable) {
                $can_be_callable_like_array = false;
                if ($param_type->hasArray()) {
                    $param_array_type = $param_type->getArray();

                    $row_type = null;
                    if ($param_array_type instanceof TArray) {
                        $row_type = $param_array_type->type_params[1];
                    } elseif ($param_array_type instanceof TKeyedArray) {
                        $row_type = $param_array_type->getGenericValueType();
                    }

                    if ($row_type &&
                        ($row_type->hasMixed() || $row_type->hasString())
                    ) {
                        $can_be_callable_like_array = true;
                    }
                }

                if (!$can_be_callable_like_array) {
                    $function_ids = CallAnalyzer::getFunctionIdsFromCallableArg(
                        $statements_analyzer,
                        $input_expr,
                    );

                    foreach ($function_ids as $function_id) {
                        if (strpos($function_id, '::') !== false) {
                            if ($function_id[0] === '$') {
                                $function_id = substr($function_id, 1);
                            }

                            $function_id_parts = explode('&', $function_id);

                            $non_existent_method_ids = [];

                            foreach ($function_id_parts as $function_id_part) {
                                [$callable_fq_class_name, $method_name] = explode('::', $function_id_part);

                                switch ($callable_fq_class_name) {
                                    case 'self':
                                    case 'static':
                                    case 'parent':
                                        $container_class = $statements_analyzer->getFQCLN();

                                        if ($callable_fq_class_name === 'parent') {
                                            $container_class = $statements_analyzer->getParentFQCLN();
                                            if ($container_class === null) {
                                                IssueBuffer::accepts(
                                                    new ParentNotFound(
                                                        'Cannot call method on parent'
                                                        . ' as this class does not extend another',
                                                        $arg_location,
                                                    ),
                                                    $statements_analyzer->getSuppressedIssues(),
                                                );
                                            }
                                        }

                                        if (!$container_class) {
                                            continue 2;
                                        }

                                        $callable_fq_class_name = $container_class;
                                }

                                if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                                    $statements_analyzer,
                                    $callable_fq_class_name,
                                    $arg_location,
                                    $context->self,
                                    $context->calling_method_id,
                                    $statements_analyzer->getSuppressedIssues(),
                                    new ClassLikeNameOptions(true),
                                ) === false
                                ) {
                                    return;
                                }

                                $function_id_part = new MethodIdentifier(
                                    $callable_fq_class_name,
                                    strtolower($method_name),
                                );

                                $call_method_id = new MethodIdentifier(
                                    $callable_fq_class_name,
                                    '__call',
                                );

                                if (!$codebase->classOrInterfaceOrEnumExists($callable_fq_class_name)) {
                                    return;
                                }

                                if (!$codebase->methods->methodExists($function_id_part)
                                    && !$codebase->methods->methodExists($call_method_id)
                                ) {
                                    $non_existent_method_ids[] = $function_id_part;
                                }
                            }

                            if ($non_existent_method_ids && !$param_type->hasString() && !$param_type->hasArray()) {
                                if (MethodAnalyzer::checkMethodExists(
                                    $codebase,
                                    $non_existent_method_ids[0],
                                    $arg_location,
                                    $statements_analyzer->getSuppressedIssues(),
                                ) === false
                                ) {
                                    return;
                                }
                            }
                        } else {
                            if (!$param_type->hasString()
                                && !$param_type->hasArray()
                                && $context->check_functions
                                && CallAnalyzer::checkFunctionExists(
                                    $statements_analyzer,
                                    $function_id,
                                    $arg_location,
                                    false,
                                ) === false
                            ) {
                                return;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param TKeyedArray|TArray|TClassStringMap $unpacked_atomic_array
     */
    private static function coerceValueAfterGatekeeperArgument(
        StatementsAnalyzer $statements_analyzer,
        Union $input_type,
        bool $input_type_changed,
        PhpParser\Node\Expr $input_expr,
        Union $param_type,
        ?Union $signature_param_type,
        Context $context,
        bool $unpack,
        ?Atomic $unpacked_atomic_array
    ): void {
        if ($param_type->hasMixed()) {
            return;
        }

        if (!$input_type_changed && $param_type->from_docblock && !$input_type->hasMixed()) {
            $types = $input_type->getAtomicTypes();
            foreach ($param_type->getAtomicTypes() as $param_atomic_type) {
                if ($param_atomic_type instanceof TGenericObject) {
                    foreach ($types as &$input_atomic_type) {
                        if ($input_atomic_type instanceof TGenericObject
                            && $input_atomic_type->value === $param_atomic_type->value
                        ) {
                            $new_type_params = [];
                            foreach ($input_atomic_type->type_params as $i => $type_param) {
                                if ($type_param->isNever() && isset($param_atomic_type->type_params[$i])) {
                                    $input_type_changed = true;

                                    $new_type_params[$i] = $param_atomic_type->type_params[$i];
                                }
                            }
                            if ($new_type_params) {
                                $input_atomic_type = new TGenericObject(
                                    $input_atomic_type->value,
                                    [...$input_atomic_type->type_params, ...$new_type_params],
                                    $input_atomic_type->remapped_params,
                                    false,
                                    $input_atomic_type->extra_types,
                                );
                            }
                        }
                    }
                    unset($input_atomic_type);
                }
            }

            if (!$input_type_changed) {
                return;
            }

            $input_type = new Union($types);
        }

        $var_id = ExpressionIdentifier::getVarId(
            $input_expr,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        );

        if ($var_id) {
            $was_cloned = false;

            if ($input_type->isNullable() && !$param_type->isNullable()) {
                $input_type = $input_type->getBuilder();
                $was_cloned = true;
                $input_type->removeType('null');
                $input_type = $input_type->freeze();
            }

            if ($input_type->getId() === $param_type->getId()) {
                if ($input_type->from_docblock) {
                    $input_type = $input_type->setFromDocblock(false);
                }
            } elseif ($input_type->hasMixed() && $signature_param_type) {
                $was_cloned = true;
                $parent_nodes = $input_type->parent_nodes;
                $by_ref = $input_type->by_ref;
                $input_type = $signature_param_type->setProperties([
                    'ignore_nullable_issues' => $signature_param_type->isNullable(),
                    'parent_nodes' => $parent_nodes,
                    'by_ref' => $by_ref,
                ]);
            }

            if ($context->inside_conditional && !isset($context->assigned_var_ids[$var_id])) {
                $context->assigned_var_ids[$var_id] = 0;
            }

            if ($was_cloned) {
                $context->removeVarFromConflictingClauses($var_id, null, $statements_analyzer);
            }

            if ($unpack) {
                if ($unpacked_atomic_array instanceof TArray) {
                    $unpacked_atomic_array = $unpacked_atomic_array->setTypeParams([
                        $unpacked_atomic_array->type_params[0],
                        $input_type,
                    ]);

                    $context->vars_in_scope[$var_id] = new Union([$unpacked_atomic_array]);
                } elseif ($unpacked_atomic_array instanceof TKeyedArray
                    && $unpacked_atomic_array->is_list
                ) {
                    if ($unpacked_atomic_array->isNonEmpty()) {
                        $unpacked_atomic_array = Type::getNonEmptyListAtomic($input_type);
                    } else {
                        $unpacked_atomic_array = Type::getListAtomic($input_type);
                    }

                    $context->vars_in_scope[$var_id] = new Union([$unpacked_atomic_array]);
                } else {
                    $context->vars_in_scope[$var_id] = new Union([
                        new TArray([
                            Type::getInt(),
                            $input_type,
                        ]),
                    ]);
                }
            } else {
                $context->vars_in_scope[$var_id] = $input_type;
            }
        }
    }

    private static function processTaintedness(
        StatementsAnalyzer $statements_analyzer,
        string $cased_method_id,
        ?MethodIdentifier $method_id,
        int $argument_offset,
        CodeLocation $arg_location,
        CodeLocation $function_call_location,
        FunctionLikeParameter $function_param,
        Union $input_type,
        PhpParser\Node\Expr $expr,
        Context $context,
        bool $specialize_taint
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        if (!$statements_analyzer->data_flow_graph
            || ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                && in_array('TaintedInput', $statements_analyzer->getSuppressedIssues()))
        ) {
            return;
        }

        // literal data can’t be tainted
        if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
            && $input_type->isSingle()
            && $input_type->hasLiteralValue()
        ) {
            return;
        }

        // numeric types can't be tainted, neither can bool
        if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
            && $input_type->isSingle()
            && ($input_type->isInt() || $input_type->isFloat() || $input_type->isBool())
        ) {
            return;
        }

        $event = new AddRemoveTaintsEvent($expr, $context, $statements_analyzer, $codebase);

        $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
        $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

        if ($function_param->type && $function_param->type->isString() && !$input_type->isString()) {
            $input_type = CastAnalyzer::castStringAttempt(
                $statements_analyzer,
                $context,
                $input_type,
                $expr,
                false,
            );
        }

        if ($specialize_taint) {
            $method_node = DataFlowNode::getForMethodArgument(
                $cased_method_id,
                $cased_method_id,
                $argument_offset,
                $statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                    ? $function_param->location
                    : null,
                $function_call_location,
            );
        } else {
            $method_node = DataFlowNode::getForMethodArgument(
                $cased_method_id,
                $cased_method_id,
                $argument_offset,
                $statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                    ? $function_param->location
                    : null,
            );

            if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                && $method_id
                && $method_id->method_name !== '__construct'
            ) {
                $fq_classlike_name = $method_id->fq_class_name;
                $method_name = $method_id->method_name;
                $cased_method_name = explode('::', $cased_method_id)[1];

                $class_storage = $codebase->classlike_storage_provider->get($fq_classlike_name);

                foreach ($class_storage->dependent_classlikes as $dependent_classlike_lc => $_) {
                    $dependent_classlike_storage = $codebase->classlike_storage_provider->get(
                        $dependent_classlike_lc,
                    );
                    $new_sink = DataFlowNode::getForMethodArgument(
                        $dependent_classlike_lc . '::' . $method_name,
                        $dependent_classlike_storage->name . '::' . $cased_method_name,
                        $argument_offset,
                        $arg_location,
                        null,
                    );

                    $statements_analyzer->data_flow_graph->addNode($new_sink);
                    $statements_analyzer->data_flow_graph->addPath(
                        $method_node,
                        $new_sink,
                        'arg',
                        $added_taints,
                        $removed_taints,
                    );
                }
            }
        }

        if ($method_id && $statements_analyzer->data_flow_graph instanceof TaintFlowGraph) {
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            if ($declaring_method_id && (string) $declaring_method_id !== (string) $method_id) {
                $new_sink = DataFlowNode::getForMethodArgument(
                    (string) $declaring_method_id,
                    $codebase->methods->getCasedMethodId($declaring_method_id),
                    $argument_offset,
                    $arg_location,
                    null,
                );

                $statements_analyzer->data_flow_graph->addNode($new_sink);
                $statements_analyzer->data_flow_graph->addPath(
                    $method_node,
                    $new_sink,
                    'arg',
                    $added_taints,
                    $removed_taints,
                );
            }
        }

        $statements_analyzer->data_flow_graph->addNode($method_node);

        $argument_value_node = DataFlowNode::getForAssignment(
            'call to ' . $cased_method_id,
            $arg_location,
        );

        $statements_analyzer->data_flow_graph->addNode($argument_value_node);

        $statements_analyzer->data_flow_graph->addPath(
            $argument_value_node,
            $method_node,
            'arg',
            $added_taints,
            $removed_taints,
        );

        foreach ($input_type->parent_nodes as $parent_node) {
            $statements_analyzer->data_flow_graph->addNode($method_node);
            $statements_analyzer->data_flow_graph->addPath(
                $parent_node,
                $argument_value_node,
                'arg',
                $added_taints,
                $removed_taints,
            );
        }
    }
}
