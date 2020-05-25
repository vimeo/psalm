<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use function array_map;
use function count;
use function explode;
use function in_array;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Type\ArrayType;
use Psalm\StatementsSource;
use Psalm\Type;
use function strpos;
use function strtolower;

class ArrayMapReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['array_map'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     * @param  CodeLocation                 $code_location
     */
    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            return Type::getMixed();
        }

        $function_call_arg = $call_args[0] ?? null;

        $function_call_type = $function_call_arg
            ? $statements_source->node_data->getType($function_call_arg->value)
            : null;

        if ($function_call_type && $function_call_type->isNull()) {
            \array_shift($call_args);

            $array_arg_types = [];

            foreach ($call_args as $call_arg) {
                $call_arg_type = $statements_source->node_data->getType($call_arg->value);

                if ($call_arg_type) {
                    $array_arg_types[] = clone $call_arg_type;
                } else {
                    $array_arg_types[] = Type::getMixed();
                    break;
                }
            }

            if ($array_arg_types) {
                return new Type\Union([new Type\Atomic\ObjectLike($array_arg_types)]);
            }

            return Type::getArray();
        }

        $array_arg = isset($call_args[1]->value) ? $call_args[1]->value : null;

        $array_arg_atomic_type = null;
        $array_arg_type = null;

        if ($array_arg && ($array_arg_union_type = $statements_source->node_data->getType($array_arg))) {
            $arg_types = $array_arg_union_type->getAtomicTypes();

            if (isset($arg_types['array'])) {
                $array_arg_atomic_type = $arg_types['array'];
                $array_arg_type = ArrayType::infer($array_arg_atomic_type);
            }
        }

        $generic_key_type = null;
        $mapping_return_type = null;

        if ($function_call_arg && $function_call_type) {
            if (count($call_args) === 2) {
                $generic_key_type = $array_arg_type->key ?? Type::getArrayKey();
            } else {
                $generic_key_type = Type::getInt();
            }

            if ($closure_types = $function_call_type->getClosureTypes()) {
                $closure_atomic_type = \reset($closure_types);

                $closure_return_type = $closure_atomic_type->return_type ?: Type::getMixed();

                if ($closure_return_type->isVoid()) {
                    $closure_return_type = Type::getNull();
                }

                $mapping_return_type = clone $closure_return_type;
            } elseif ($function_call_arg->value instanceof PhpParser\Node\Scalar\String_
                || $function_call_arg->value instanceof PhpParser\Node\Expr\Array_
                || $function_call_arg->value instanceof PhpParser\Node\Expr\BinaryOp\Concat
            ) {
                $mapping_function_ids = CallAnalyzer::getFunctionIdsFromCallableArg(
                    $statements_source,
                    $function_call_arg->value
                );

                if ($mapping_function_ids) {
                    $mapping_return_type = self::getReturnTypeFromMappingIds(
                        $statements_source,
                        $mapping_function_ids,
                        $context,
                        $function_call_arg,
                        $array_arg_type
                    );
                }

                if ($function_call_arg->value instanceof PhpParser\Node\Expr\Array_
                    && isset($function_call_arg->value->items[0])
                    && isset($function_call_arg->value->items[1])
                    && $function_call_arg->value->items[1]->value instanceof PhpParser\Node\Scalar\String_
                    && $function_call_arg->value->items[0]->value instanceof PhpParser\Node\Expr\Variable
                    && ($variable_type
                        = $statements_source->node_data->getType($function_call_arg->value->items[0]->value))
                ) {
                    $fake_method_call = null;

                    foreach ($variable_type->getAtomicTypes() as $variable_atomic_type) {
                        if ($variable_atomic_type instanceof Type\Atomic\TTemplateParam
                            || $variable_atomic_type instanceof Type\Atomic\TTemplateParamClass
                        ) {
                            $fake_method_call = new PhpParser\Node\Expr\StaticCall(
                                $function_call_arg->value->items[0]->value,
                                $function_call_arg->value->items[1]->value->value,
                                []
                            );
                        } elseif ($variable_atomic_type instanceof Type\Atomic\TTemplateParamClass) {
                            $fake_method_call = new PhpParser\Node\Expr\StaticCall(
                                $function_call_arg->value->items[0]->value,
                                $function_call_arg->value->items[1]->value->value,
                                []
                            );
                        }
                    }

                    if ($fake_method_call) {
                        $fake_method_return_type = self::executeFakeCall(
                            $statements_source,
                            $fake_method_call,
                            $context
                        );

                        if ($fake_method_return_type) {
                            $mapping_return_type = $fake_method_return_type;
                        }
                    }
                }
            }
        }

        if ($mapping_return_type && $generic_key_type) {
            if ($array_arg_atomic_type instanceof Type\Atomic\ObjectLike && count($call_args) === 2) {
                $atomic_type = new Type\Atomic\ObjectLike(
                    array_map(
                        /**
                        * @return Type\Union
                        */
                        function (Type\Union $_) use ($mapping_return_type) {
                            return clone $mapping_return_type;
                        },
                        $array_arg_atomic_type->properties
                    )
                );
                $atomic_type->is_list = $array_arg_atomic_type->is_list;

                return new Type\Union([$atomic_type]);
            }

            if ($array_arg_atomic_type instanceof Type\Atomic\TList
                || count($call_args) !== 2
            ) {
                if ($array_arg_atomic_type instanceof Type\Atomic\TNonEmptyList) {
                    return new Type\Union([
                        new Type\Atomic\TNonEmptyList(
                            $mapping_return_type
                        ),
                    ]);
                }

                return new Type\Union([
                    new Type\Atomic\TList(
                        $mapping_return_type
                    ),
                ]);
            }

            if ($array_arg_atomic_type instanceof Type\Atomic\TNonEmptyArray) {
                return new Type\Union([
                    new Type\Atomic\TNonEmptyArray([
                        $generic_key_type,
                        $mapping_return_type,
                    ]),
                ]);
            }

            return new Type\Union([
                new Type\Atomic\TArray([
                    $generic_key_type,
                    $mapping_return_type,
                ])
            ]);
        }

        return count($call_args) === 2 && !($array_arg_type->is_list ?? false)
            ? new Type\Union([
                new Type\Atomic\TArray([
                    $array_arg_type->key ?? Type::getArrayKey(),
                    Type::getMixed(),
                ])
            ])
            : Type::getList();
    }

    private static function executeFakeCall(
        \Psalm\Internal\Analyzer\StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $fake_method_call,
        Context $context
    ) : ?Type\Union {
        $old_data_provider = $statements_analyzer->node_data;

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        $suppressed_issues = $statements_analyzer->getSuppressedIssues();

        if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['PossiblyInvalidMethodCall']);
        }

        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        \Psalm\Internal\Analyzer\Statements\Expression\Call\StaticCallAnalyzer::analyze(
            $statements_analyzer,
            $fake_method_call,
            $context
        );

        $context->inside_call = $was_inside_call;

        if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['PossiblyInvalidMethodCall']);
        }

        $return_type = $statements_analyzer->node_data->getType($fake_method_call) ?: null;

        $statements_analyzer->node_data = $old_data_provider;

        return $return_type;
    }

    /**
     * @param non-empty-array<string> $mapping_function_ids
     */
    private static function getReturnTypeFromMappingIds(
        \Psalm\Internal\Analyzer\StatementsAnalyzer $statements_source,
        array $mapping_function_ids,
        Context $context,
        PhpParser\Node\Arg $function_call_arg,
        ?\Psalm\Internal\Type\ArrayType $array_arg_type
    ) : Type\Union {
        $call_map = InternalCallMapHandler::getCallMap();

        $mapping_return_type = null;
        $closure_param_type = null;

        $codebase = $statements_source->getCodebase();

        foreach ($mapping_function_ids as $mapping_function_id) {
            $mapping_function_id = strtolower($mapping_function_id);

            $mapping_function_id_parts = explode('&', $mapping_function_id);

            $function_id_return_type = null;

            foreach ($mapping_function_id_parts as $mapping_function_id_part) {
                if (isset($call_map[$mapping_function_id_part][0])) {
                    if ($call_map[$mapping_function_id_part][0]) {
                        $mapped_function_return =
                            Type::parseString($call_map[$mapping_function_id_part][0]);

                        if ($function_id_return_type) {
                            $function_id_return_type = Type::combineUnionTypes(
                                $function_id_return_type,
                                $mapped_function_return
                            );
                        } else {
                            $function_id_return_type = $mapped_function_return;
                        }
                    }
                } else {
                    if (strpos($mapping_function_id_part, '::') !== false) {
                        $method_id_parts = explode('::', $mapping_function_id_part);
                        $callable_fq_class_name = $method_id_parts[0];

                        if (in_array($callable_fq_class_name, ['self', 'static', 'parent'], true)) {
                            continue;
                        }

                        if (!$codebase->classlikes->classOrInterfaceExists($callable_fq_class_name)) {
                            continue;
                        }

                        $class_storage = $codebase->classlike_storage_provider->get($callable_fq_class_name);

                        $method_id = new \Psalm\Internal\MethodIdentifier(
                            $callable_fq_class_name,
                            $method_id_parts[1]
                        );

                        if (!$codebase->methods->methodExists(
                            $method_id,
                            !$context->collect_initializations
                                && !$context->collect_mutations
                                ? $context->calling_method_id
                                : null,
                            $codebase->collect_locations
                                ? new CodeLocation(
                                    $statements_source,
                                    $function_call_arg->value
                                ) : null,
                            null,
                            $statements_source->getFilePath()
                        )) {
                            continue;
                        }

                        $params = $codebase->methods->getMethodParams(
                            $method_id,
                            $statements_source
                        );

                        if (isset($params[0]->type)) {
                            $closure_param_type = $params[0]->type;
                        }

                        $self_class = 'self';

                        $return_type = $codebase->methods->getMethodReturnType(
                            new \Psalm\Internal\MethodIdentifier(...$method_id_parts),
                            $self_class
                        ) ?: Type::getMixed();

                        $static_class = $self_class;

                        if ($self_class !== 'self') {
                            $static_class = $class_storage->name;
                        }

                        $return_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                            $codebase,
                            $return_type,
                            $self_class,
                            $static_class,
                            $class_storage->parent_class
                        );

                        if ($function_id_return_type) {
                            $function_id_return_type = Type::combineUnionTypes(
                                $function_id_return_type,
                                $return_type
                            );
                        } else {
                            $function_id_return_type = $return_type;
                        }
                    } else {
                        if (!$mapping_function_id_part
                            || !$codebase->functions->functionExists(
                                $statements_source,
                                $mapping_function_id_part
                            )
                        ) {
                            $function_id_return_type = Type::getMixed();
                            continue;
                        }

                        $function_storage = $codebase->functions->getStorage(
                            $statements_source,
                            $mapping_function_id_part
                        );

                        if (isset($function_storage->params[0]->type)) {
                            $closure_param_type = $function_storage->params[0]->type;
                        }

                        $return_type = $function_storage->return_type ?: Type::getMixed();

                        if ($function_id_return_type) {
                            $function_id_return_type = Type::combineUnionTypes(
                                $function_id_return_type,
                                $return_type
                            );
                        } else {
                            $function_id_return_type = $return_type;
                        }
                    }
                }
            }

            if ($function_id_return_type === null) {
                $mapping_return_type = Type::getMixed();
            } elseif (!$mapping_return_type) {
                $mapping_return_type = $function_id_return_type;
            } else {
                $mapping_return_type = Type::combineUnionTypes(
                    $function_id_return_type,
                    $mapping_return_type,
                    $codebase
                );
            }
        }

        if ($closure_param_type
            && $mapping_return_type->hasTemplate()
            && $array_arg_type
        ) {
            $mapping_return_type = clone $mapping_return_type;

            $template_types = [];

            foreach ($closure_param_type->getTemplateTypes() as $template_type) {
                $template_types[$template_type->param_name] = [
                    ($template_type->defining_class) => [$template_type->as]
                ];
            }

            $template_result = new \Psalm\Internal\Type\TemplateResult(
                $template_types,
                []
            );

            \Psalm\Internal\Type\UnionTemplateHandler::replaceTemplateTypesWithStandins(
                $closure_param_type,
                $template_result,
                $codebase,
                $statements_source,
                $array_arg_type->value,
                0,
                $context->self,
                $context->calling_method_id ?: $context->calling_function_id
            );

            $mapping_return_type->replaceTemplateTypesWithArgTypes(
                $template_result,
                $codebase
            );
        }

        return $mapping_return_type;
    }
}
