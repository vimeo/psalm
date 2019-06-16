<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\StatementsSource;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Internal\Codebase\CallMap;
use Psalm\IssueBuffer;
use Psalm\Issue\InvalidArgument;

class ArrayReduceReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['array_reduce'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     */
    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        if (!isset($call_args[0]) || !isset($call_args[1])) {
            return Type::getMixed();
        }

        $codebase = $statements_source->getCodebase();

        $array_arg = $call_args[0]->value;
        $function_call_arg = $call_args[1]->value;

        if (!isset($array_arg->inferredType) || !isset($function_call_arg->inferredType)) {
            return Type::getMixed();
        }

        $array_arg_type = null;

        $array_arg_types = $array_arg->inferredType->getTypes();

        if (isset($array_arg_types['array'])
            && ($array_arg_types['array'] instanceof Type\Atomic\TArray
                || $array_arg_types['array'] instanceof Type\Atomic\ObjectLike)
        ) {
            $array_arg_type = $array_arg_types['array'];

            if ($array_arg_type instanceof Type\Atomic\ObjectLike) {
                $array_arg_type = $array_arg_type->getGenericArrayType();
            }
        }

        if (!isset($call_args[2])) {
            $reduce_return_type = Type::getNull();
            $reduce_return_type->ignore_nullable_issues = true;
        } else {
            if (!isset($call_args[2]->value->inferredType)) {
                return Type::getMixed();
            }

            $reduce_return_type = $call_args[2]->value->inferredType;

            if ($reduce_return_type->hasMixed()) {
                return Type::getMixed();
            }
        }

        $initial_type = $reduce_return_type;

        if (($first_arg_atomic_types = $function_call_arg->inferredType->getTypes())
            && ($closure_atomic_type = isset($first_arg_atomic_types['Closure'])
                ? $first_arg_atomic_types['Closure']
                : null)
            && $closure_atomic_type instanceof Type\Atomic\TFn
        ) {
            $closure_return_type = $closure_atomic_type->return_type ?: Type::getMixed();

            if ($closure_return_type->isVoid()) {
                $closure_return_type = Type::getNull();
            }

            $reduce_return_type = Type::combineUnionTypes($closure_return_type, $reduce_return_type);

            if ($closure_atomic_type->params !== null) {
                if (count($closure_atomic_type->params) < 2) {
                    if (IssueBuffer::accepts(
                        new InvalidArgument(
                            'The closure passed to array_reduce needs two params',
                            new CodeLocation($statements_source, $function_call_arg)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    return Type::getMixed();
                }

                $carry_param = $closure_atomic_type->params[0];
                $item_param = $closure_atomic_type->params[1];

                if ($carry_param->type
                    && (!TypeAnalyzer::isContainedBy(
                        $codebase,
                        $initial_type,
                        $carry_param->type
                    ) || (!$reduce_return_type->hasMixed()
                            && !TypeAnalyzer::isContainedBy(
                                $codebase,
                                $reduce_return_type,
                                $carry_param->type
                            )
                        )
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidArgument(
                            'The first param of the closure passed to array_reduce must take '
                                . $reduce_return_type . ' but only accepts ' . $carry_param->type,
                            $carry_param->type_location
                                ?: new CodeLocation($statements_source, $function_call_arg)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    return Type::getMixed();
                }

                if ($item_param->type
                    && $array_arg_type
                    && !$array_arg_type->type_params[1]->hasMixed()
                    && !TypeAnalyzer::isContainedBy(
                        $codebase,
                        $array_arg_type->type_params[1],
                        $item_param->type
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidArgument(
                            'The second param of the closure passed to array_reduce must take '
                                . $array_arg_type->type_params[1] . ' but only accepts ' . $item_param->type,
                            $item_param->type_location
                                ?: new CodeLocation($statements_source, $function_call_arg)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    return Type::getMixed();
                }
            }

            return $reduce_return_type;
        }

        if ($function_call_arg instanceof PhpParser\Node\Scalar\String_
            || $function_call_arg instanceof PhpParser\Node\Expr\Array_
            || $function_call_arg instanceof PhpParser\Node\Expr\BinaryOp\Concat
        ) {
            $mapping_function_ids = CallAnalyzer::getFunctionIdsFromCallableArg(
                $statements_source,
                $function_call_arg
            );

            $call_map = CallMap::getCallMap();

            foreach ($mapping_function_ids as $mapping_function_id) {
                $mapping_function_id = strtolower($mapping_function_id);

                $mapping_function_id_parts = explode('&', $mapping_function_id);

                $part_match_found = false;

                foreach ($mapping_function_id_parts as $mapping_function_id_part) {
                    if (isset($call_map[$mapping_function_id_part][0])) {
                        if ($call_map[$mapping_function_id_part][0]) {
                            $mapped_function_return =
                                Type::parseString($call_map[$mapping_function_id_part][0]);

                            $reduce_return_type = Type::combineUnionTypes(
                                $reduce_return_type,
                                $mapped_function_return
                            );

                            $part_match_found = true;
                        }
                    } else {
                        if (strpos($mapping_function_id_part, '::') !== false) {
                            list($callable_fq_class_name) = explode('::', $mapping_function_id_part);

                            if (in_array($callable_fq_class_name, ['self', 'static', 'parent'], true)) {
                                continue;
                            }

                            if (!$codebase->methods->methodExists(
                                $mapping_function_id_part,
                                $context->calling_method_id,
                                $codebase->collect_references
                                    ? new CodeLocation(
                                        $statements_source,
                                        $function_call_arg
                                    ) : null,
                                null,
                                $statements_source->getFilePath()
                            )) {
                                continue;
                            }

                            $part_match_found = true;

                            $self_class = 'self';

                            $return_type = $codebase->methods->getMethodReturnType(
                                $mapping_function_id_part,
                                $self_class
                            ) ?: Type::getMixed();

                            $reduce_return_type = Type::combineUnionTypes(
                                $reduce_return_type,
                                $return_type
                            );
                        } else {
                            if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer
                                || !$codebase->functions->functionExists(
                                    $statements_source,
                                    $mapping_function_id_part
                                )
                            ) {
                                return Type::getMixed();
                            }

                            $part_match_found = true;

                            $function_storage = $codebase->functions->getStorage(
                                $statements_source,
                                $mapping_function_id_part
                            );

                            $return_type = $function_storage->return_type ?: Type::getMixed();

                            $reduce_return_type = Type::combineUnionTypes(
                                $reduce_return_type,
                                $return_type
                            );
                        }
                    }
                }

                if ($part_match_found === false) {
                    return Type::getMixed();
                }
            }

            return $reduce_return_type;
        }

        return Type::getMixed();
    }
}
