<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;
use ReflectionProperty;

use function count;
use function is_string;
use function str_contains;
use function str_replace;
use function strtolower;

/**
 * @internal
 */
final class PhpStormMetaScanner
{
    /**
     * @param  list<PhpParser\Node\Arg> $args
     */
    public static function handleOverride(array $args, Codebase $codebase): void
    {
        if (count($args) < 2) {
            return;
        }

        $identifier = $args[0]->value;

        if (!$args[1]->value instanceof PhpParser\Node\Expr\FuncCall
            || !$args[1]->value->name instanceof PhpParser\Node\Name
        ) {
            return;
        }

        $map = [];

        if ($args[1]->value->name->getParts() === ['map']
            && $args[1]->value->getArgs()
            && $args[1]->value->getArgs()[0]->value instanceof PhpParser\Node\Expr\Array_
        ) {
            foreach ($args[1]->value->getArgs()[0]->value->items as $array_item) {
                if ($array_item
                    && $array_item->key instanceof PhpParser\Node\Scalar\String_
                ) {
                    if ($array_item->value instanceof PhpParser\Node\Expr\ClassConstFetch
                        && $array_item->value->class instanceof PhpParser\Node\Name\FullyQualified
                        && $array_item->value->name instanceof PhpParser\Node\Identifier
                        && strtolower($array_item->value->name->name)
                    ) {
                        $map[$array_item->key->value] = new Union([
                            new TNamedObject($array_item->value->class->toString()),
                        ]);
                    } elseif ($array_item->value instanceof PhpParser\Node\Scalar\String_) {
                        $map[$array_item->key->value] = $array_item->value->value;
                    }
                } elseif ($array_item
                    && $array_item->key instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $array_item->key->class instanceof PhpParser\Node\Name\FullyQualified
                    && $array_item->key->name instanceof PhpParser\Node\Identifier
                ) {
                    /** @var string|null $resolved_name */
                    $resolved_name =  $array_item->key->class->getAttribute('resolvedName');
                    if (!$resolved_name) {
                        continue;
                    }

                    $constant_type = $codebase->classlikes->getClassConstantType(
                        $resolved_name,
                        $array_item->key->name->name,
                        ReflectionProperty::IS_PRIVATE,
                    );

                    if (!$constant_type instanceof Union || !$constant_type->isSingleStringLiteral()) {
                        continue;
                    }

                    $meta_key = $constant_type->getSingleStringLiteral()->value;

                    if ($array_item->value instanceof PhpParser\Node\Expr\ClassConstFetch
                        && $array_item->value->class instanceof PhpParser\Node\Name\FullyQualified
                        && $array_item->value->name instanceof PhpParser\Node\Identifier
                        && strtolower($array_item->value->name->name)
                    ) {
                        $map[$meta_key] = new Union([
                            new TNamedObject($array_item->value->class->toString()),
                        ]);
                    } elseif ($array_item->value instanceof PhpParser\Node\Scalar\String_) {
                        $map[$meta_key] = $array_item->value->value;
                    }
                }
            }
        }

        $type_offset = null;

        if ($args[1]->value->name->getParts() === ['type']
            && $args[1]->value->getArgs()
            && $args[1]->value->getArgs()[0]->value instanceof PhpParser\Node\Scalar\Int_
        ) {
            $type_offset = $args[1]->value->getArgs()[0]->value->value;
        }

        $element_type_offset = null;

        if ($args[1]->value->name->getParts() === ['elementType']
            && $args[1]->value->getArgs()
            && $args[1]->value->getArgs()[0]->value instanceof PhpParser\Node\Scalar\Int_
        ) {
            $element_type_offset = $args[1]->value->getArgs()[0]->value->value;
        }

        if ($identifier instanceof PhpParser\Node\Expr\StaticCall
            && $identifier->class instanceof PhpParser\Node\Name\FullyQualified
            && $identifier->name instanceof PhpParser\Node\Identifier
            && (
                $identifier->getArgs() === []
                || $identifier->getArgs()[0]->value instanceof PhpParser\Node\Scalar\Int_
            )
        ) {
            $meta_fq_classlike_name = $identifier->class->toString();

            $meta_method_name = strtolower($identifier->name->name);

            if ($map) {
                $offset = 0;
                if ($identifier->getArgs()
                    && $identifier->getArgs()[0]->value instanceof PhpParser\Node\Scalar\Int_
                ) {
                    $offset = $identifier->getArgs()[0]->value->value;
                }

                $codebase->methods->return_type_provider->registerClosure(
                    $meta_fq_classlike_name,
                    static function (
                        MethodReturnTypeProviderEvent $event,
                    ) use (
                        $map,
                        $offset,
                        $meta_fq_classlike_name,
                        $meta_method_name,
                    ): ?Union {
                        $statements_analyzer = $event->getSource();
                        $call_args = $event->getCallArgs();
                        $method_name = $event->getMethodNameLowercase();
                        $fq_classlike_name = $event->getFqClasslikeName();
                        if (!$statements_analyzer instanceof StatementsAnalyzer) {
                            return Type::getMixed();
                        }

                        if ($meta_method_name !== $method_name
                            || $meta_fq_classlike_name !== $fq_classlike_name
                        ) {
                            return null;
                        }

                        if (isset($call_args[$offset]->value)
                            && ($call_arg_type = $statements_analyzer->node_data->getType($call_args[$offset]->value))
                            && $call_arg_type->isSingleStringLiteral()
                        ) {
                            $offset_arg_value = $call_arg_type->getSingleStringLiteral()->value;

                            if ($mapped_type = $map[$offset_arg_value] ?? null) {
                                if ($mapped_type instanceof Union) {
                                    return $mapped_type;
                                }
                            }

                            if (($mapped_type = $map[''] ?? null) && is_string($mapped_type)) {
                                if (str_contains($mapped_type, '@')) {
                                    $mapped_type = str_replace('@', $offset_arg_value, $mapped_type);

                                    if (!str_contains($mapped_type, '.')) {
                                        return new Union([
                                            new TNamedObject($mapped_type),
                                        ]);
                                    }
                                }
                            }
                        }

                        return null;
                    },
                );
            } elseif ($type_offset !== null) {
                $codebase->methods->return_type_provider->registerClosure(
                    $meta_fq_classlike_name,
                    static function (
                        MethodReturnTypeProviderEvent $event,
                    ) use (
                        $type_offset,
                        $meta_fq_classlike_name,
                        $meta_method_name,
                    ): ?Union {
                        $statements_analyzer = $event->getSource();
                        $call_args = $event->getCallArgs();
                        $method_name = $event->getMethodNameLowercase();
                        $fq_classlike_name = $event->getFqClasslikeName();
                        if (!$statements_analyzer instanceof StatementsAnalyzer) {
                            return Type::getMixed();
                        }

                        if ($meta_method_name !== $method_name
                            || $meta_fq_classlike_name !== $fq_classlike_name
                        ) {
                            return null;
                        }

                        if (isset($call_args[$type_offset]->value)
                            && ($call_arg_type
                                = $statements_analyzer->node_data->getType($call_args[$type_offset]->value))
                        ) {
                            return $call_arg_type;
                        }

                        return null;
                    },
                );
            } elseif ($element_type_offset !== null) {
                $codebase->methods->return_type_provider->registerClosure(
                    $meta_fq_classlike_name,
                    static function (
                        MethodReturnTypeProviderEvent $event,
                    ) use (
                        $element_type_offset,
                        $meta_fq_classlike_name,
                        $meta_method_name,
                    ): ?Union {
                        $statements_analyzer = $event->getSource();
                        $call_args = $event->getCallArgs();
                        $method_name = $event->getMethodNameLowercase();
                        $fq_classlike_name = $event->getFqClasslikeName();
                        if (!$statements_analyzer instanceof StatementsAnalyzer) {
                            return Type::getMixed();
                        }

                        if ($meta_method_name !== $method_name
                            || $meta_fq_classlike_name !== $fq_classlike_name
                        ) {
                            return null;
                        }

                        if (isset($call_args[$element_type_offset]->value)
                            && ($call_arg_type
                                = $statements_analyzer->node_data->getType($call_args[$element_type_offset]->value))
                            && $call_arg_type->hasArray()
                        ) {
                            /**
                             * @var TArray|TKeyedArray
                             */
                            $array_atomic_type = $call_arg_type->getArray();

                            if ($array_atomic_type instanceof TKeyedArray) {
                                return $array_atomic_type->getGenericValueType();
                            }

                            return $array_atomic_type->type_params[1];
                        }

                        return null;
                    },
                );
            }
        }

        if ($identifier instanceof PhpParser\Node\Expr\FuncCall
            && $identifier->name instanceof PhpParser\Node\Name\FullyQualified
            && (
                $identifier->getArgs() === []
                || $identifier->getArgs()[0]->value instanceof PhpParser\Node\Scalar\Int_
            )
        ) {
            $function_id = strtolower($identifier->name->toString());

            if ($map) {
                $offset = 0;
                if ($identifier->getArgs()
                    && $identifier->getArgs()[0]->value instanceof PhpParser\Node\Scalar\Int_
                ) {
                    $offset = $identifier->getArgs()[0]->value->value;
                }

                $codebase->functions->return_type_provider->registerClosure(
                    $function_id,
                    static function (
                        FunctionReturnTypeProviderEvent $event,
                    ) use (
                        $map,
                        $offset,
                    ): Union {
                        $statements_analyzer = $event->getStatementsSource();
                        $call_args = $event->getCallArgs();
                        $function_id = $event->getFunctionId();
                        if (!$statements_analyzer instanceof StatementsAnalyzer) {
                            return Type::getMixed();
                        }

                        if (isset($call_args[$offset]->value)
                            && ($call_arg_type
                                = $statements_analyzer->node_data->getType($call_args[$offset]->value))
                            && $call_arg_type->isSingleStringLiteral()
                        ) {
                            $offset_arg_value = $call_arg_type->getSingleStringLiteral()->value;

                            if ($mapped_type = $map[$offset_arg_value] ?? null) {
                                if ($mapped_type instanceof Union) {
                                    return $mapped_type;
                                }
                            }

                            if (($mapped_type = $map[''] ?? null) && is_string($mapped_type)) {
                                if (str_contains($mapped_type, '@')) {
                                    $mapped_type = str_replace('@', $offset_arg_value, $mapped_type);

                                    if (!str_contains($mapped_type, '.')) {
                                        return new Union([
                                            new TNamedObject($mapped_type),
                                        ]);
                                    }
                                }
                            }
                        }

                        $storage = $statements_analyzer->getCodebase()->functions->getStorage(
                            $statements_analyzer,
                            strtolower($function_id),
                        );

                        return $storage->return_type ?: Type::getMixed();
                    },
                );
            } elseif ($type_offset !== null) {
                $codebase->functions->return_type_provider->registerClosure(
                    $function_id,
                    static function (
                        FunctionReturnTypeProviderEvent $event,
                    ) use (
                        $type_offset,
                    ): Union {
                        $statements_analyzer = $event->getStatementsSource();
                        $call_args = $event->getCallArgs();
                        $function_id = $event->getFunctionId();
                        if (!$statements_analyzer instanceof StatementsAnalyzer) {
                            return Type::getMixed();
                        }

                        if (isset($call_args[$type_offset]->value)
                            && ($call_arg_type
                                = $statements_analyzer->node_data->getType($call_args[$type_offset]->value))
                        ) {
                            return $call_arg_type;
                        }

                        $storage = $statements_analyzer->getCodebase()->functions->getStorage(
                            $statements_analyzer,
                            strtolower($function_id),
                        );

                        return $storage->return_type ?: Type::getMixed();
                    },
                );
            } elseif ($element_type_offset !== null) {
                $codebase->functions->return_type_provider->registerClosure(
                    $function_id,
                    static function (
                        FunctionReturnTypeProviderEvent $event,
                    ) use (
                        $element_type_offset,
                    ): Union {
                        $statements_analyzer = $event->getStatementsSource();
                        $call_args = $event->getCallArgs();
                        $function_id = $event->getFunctionId();
                        if (!$statements_analyzer instanceof StatementsAnalyzer) {
                            return Type::getMixed();
                        }

                        if (isset($call_args[$element_type_offset]->value)
                            && ($call_arg_type
                                = $statements_analyzer->node_data->getType($call_args[$element_type_offset]->value))
                            && $call_arg_type->hasArray()
                        ) {
                            /**
                             * @var TArray|TKeyedArray
                             */
                            $array_atomic_type = $call_arg_type->getArray();

                            if ($array_atomic_type instanceof TKeyedArray) {
                                return $array_atomic_type->getGenericValueType();
                            }

                            return $array_atomic_type->type_params[1];
                        }

                        $storage = $statements_analyzer->getCodebase()->functions->getStorage(
                            $statements_analyzer,
                            strtolower($function_id),
                        );

                        return $storage->return_type ?: Type::getMixed();
                    },
                );
            }
        }
    }
}
