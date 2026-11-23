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
                if ($array_item->key instanceof PhpParser\Node\Scalar\String_) {
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
                } elseif ($array_item->key instanceof PhpParser\Node\Expr\ClassConstFetch
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

        if (!$identifier instanceof PhpParser\Node\Expr\StaticCall
            && !$identifier instanceof PhpParser\Node\Expr\FuncCall
        ) {
            return;
        }

        $identifier_args = $identifier->getArgs();

        if ($identifier_args !== [] && !$identifier_args[0]->value instanceof PhpParser\Node\Scalar\Int_) {
            return;
        }

        $override_args = $args[1]->value->getArgs();
        $override_offset = $override_args && $override_args[0]->value instanceof PhpParser\Node\Scalar\Int_
            ? $override_args[0]->value->value
            : null;

        if ($map) {
            $offset = $identifier_args ? $identifier_args[0]->value->value : 0;

            $resolve = static fn(StatementsAnalyzer $statements_analyzer, array $call_args): ?Union
                => self::resolveMapOverride($statements_analyzer, $call_args, $offset, $map);
        } elseif ($override_offset !== null && $args[1]->value->name->getParts() === ['type']) {
            $resolve = static fn(StatementsAnalyzer $statements_analyzer, array $call_args): ?Union
                => self::resolveTypeOverride($statements_analyzer, $call_args, $override_offset);
        } elseif ($override_offset !== null && $args[1]->value->name->getParts() === ['elementType']) {
            $resolve = static fn(StatementsAnalyzer $statements_analyzer, array $call_args): ?Union
                => self::resolveElementTypeOverride($statements_analyzer, $call_args, $override_offset);
        } else {
            return;
        }

        if ($identifier instanceof PhpParser\Node\Expr\StaticCall
            && $identifier->class instanceof PhpParser\Node\Name\FullyQualified
            && $identifier->name instanceof PhpParser\Node\Identifier
        ) {
            $meta_fq_classlike_name = $identifier->class->toString();

            $meta_method_name = strtolower($identifier->name->name);

            $codebase->methods->return_type_provider->registerClosure(
                $meta_fq_classlike_name,
                static function (
                    MethodReturnTypeProviderEvent $event,
                ) use (
                    $resolve,
                    $meta_fq_classlike_name,
                    $meta_method_name,
                ): ?Union {
                    $statements_analyzer = $event->getSource();
                    if (!$statements_analyzer instanceof StatementsAnalyzer) {
                        return Type::getMixed();
                    }

                    if ($meta_method_name !== $event->getMethodNameLowercase()
                        || $meta_fq_classlike_name !== $event->getFqClasslikeName()
                    ) {
                        return null;
                    }

                    return $resolve($statements_analyzer, $event->getCallArgs());
                },
            );
        }

        if ($identifier instanceof PhpParser\Node\Expr\FuncCall
            && $identifier->name instanceof PhpParser\Node\Name\FullyQualified
        ) {
            $codebase->functions->return_type_provider->registerClosure(
                strtolower($identifier->name->toString()),
                static function (
                    FunctionReturnTypeProviderEvent $event,
                ) use ($resolve): ?Union {
                    $statements_analyzer = $event->getStatementsSource();
                    if (!$statements_analyzer instanceof StatementsAnalyzer) {
                        return Type::getMixed();
                    }

                    return $resolve($statements_analyzer, $event->getCallArgs());
                },
            );
        }
    }

    /**
     * @param list<PhpParser\Node\Arg> $call_args
     * @param array<string, Union|string> $map
     */
    private static function resolveMapOverride(
        StatementsAnalyzer $statements_analyzer,
        array $call_args,
        int $offset,
        array $map,
    ): ?Union {
        if (!isset($call_args[$offset])
            || !($call_arg_type = $statements_analyzer->node_data->getType($call_args[$offset]->value))
            || !$call_arg_type->isSingleStringLiteral()
        ) {
            return null;
        }

        $offset_arg_value = $call_arg_type->getSingleStringLiteral()->value;

        if (($mapped_type = $map[$offset_arg_value] ?? null) instanceof Union) {
            return $mapped_type;
        }

        if (($mapped_type = $map[''] ?? null) && is_string($mapped_type) && str_contains($mapped_type, '@')) {
            $mapped_type = str_replace('@', $offset_arg_value, $mapped_type);

            if (!str_contains($mapped_type, '.')) {
                return new Union([new TNamedObject($mapped_type)]);
            }
        }

        return null;
    }

    /**
     * @param list<PhpParser\Node\Arg> $call_args
     */
    private static function resolveTypeOverride(
        StatementsAnalyzer $statements_analyzer,
        array $call_args,
        int $offset,
    ): ?Union {
        if (!isset($call_args[$offset])) {
            return null;
        }

        return $statements_analyzer->node_data->getType($call_args[$offset]->value);
    }

    /**
     * @param list<PhpParser\Node\Arg> $call_args
     */
    private static function resolveElementTypeOverride(
        StatementsAnalyzer $statements_analyzer,
        array $call_args,
        int $offset,
    ): ?Union {
        if (!isset($call_args[$offset])
            || !($call_arg_type = $statements_analyzer->node_data->getType($call_args[$offset]->value))
            || !$call_arg_type->hasArray()
        ) {
            return null;
        }

        /**
         * @var TArray|TKeyedArray
         */
        $array_atomic_type = $call_arg_type->getArray();

        if ($array_atomic_type instanceof TKeyedArray) {
            return $array_atomic_type->getGenericValueType();
        }

        return $array_atomic_type->type_params[1];
    }
}
