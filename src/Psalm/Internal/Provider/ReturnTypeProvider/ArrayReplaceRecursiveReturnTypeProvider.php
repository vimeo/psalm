<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

/**
 * array_replace_recursive() returns the keys of all its arrays, each with the value of the last array having it, or
 * the arrays they all have under it merged the same way: a value is one of the values of the arrays, an array value
 * being any array of their keys and values.
 *
 * @internal
 */
final class ArrayReplaceRecursiveReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['array_replace_recursive'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();

        if (!$statements_source instanceof StatementsAnalyzer || $call_args === []) {
            return null;
        }

        $codebase = $statements_source->getCodebase();

        $key_types = [];
        $value_types = [];
        $is_list = true;
        $is_non_empty = false;

        foreach ($call_args as $call_arg) {
            $arg_type = $call_arg->unpack ? null : $statements_source->node_data->getType($call_arg->value);

            if ($arg_type === null || !$arg_type->isArray()) {
                return null;
            }

            $arg_is_non_empty = true;

            foreach ($arg_type->getAtomicTypes() as $atomic) {
                if ($atomic instanceof TKeyedArray) {
                    $key_types[] = $atomic->getGenericKeyType();
                    $value_types[] = $atomic->getGenericValueType();
                    $is_list = $is_list && $atomic->is_list;
                    $arg_is_non_empty = $arg_is_non_empty && $atomic->isNonEmpty();
                } elseif ($atomic instanceof TArray) {
                    if ($atomic->isEmptyArray()) {
                        $arg_is_non_empty = false;

                        continue;
                    }

                    [$key_types[], $value_types[]] = $atomic->type_params;
                    $is_list = false;
                    $arg_is_non_empty = $arg_is_non_empty && $atomic instanceof TNonEmptyArray;
                } else {
                    return null;
                }
            }

            $is_non_empty = $is_non_empty || $arg_is_non_empty;
        }

        if ($key_types === [] || $value_types === []) {
            return Type::getEmptyArray();
        }

        $key_type = Type::combineUnionTypeArray($key_types, $codebase);
        $value_type = self::getMergedValueType(Type::combineUnionTypeArray($value_types, $codebase), $codebase);

        if ($is_list) {
            return $is_non_empty ? Type::getNonEmptyList($value_type) : Type::getList($value_type);
        }

        return new Union([$is_non_empty
            ? new TNonEmptyArray([$key_type, $value_type])
            : new TArray([$key_type, $value_type]),
        ]);
    }

    /**
     * $value_type, with the arrays it can be as any array of their keys and values (merged the same way): two arrays
     * under the same key are merged into one holding the keys of both
     */
    private static function getMergedValueType(Union $value_type, Codebase $codebase): Union
    {
        $key_types = [];
        $value_types = [];
        $other_atomics = [];

        foreach ($value_type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TKeyedArray) {
                $key_types[] = $atomic->getGenericKeyType();
                $value_types[] = $atomic->getGenericValueType();
            } elseif ($atomic instanceof TArray) {
                if (!$atomic->isEmptyArray()) {
                    [$key_types[], $value_types[]] = $atomic->type_params;
                }
            } else {
                // a template that can be an array may be merged with another array: that array is any array
                if ($atomic instanceof TTemplateParam && ($atomic->as->hasArray() || $atomic->as->hasMixed())) {
                    $key_types[] = Type::getArrayKey();
                    $value_types[] = Type::getMixed();
                }

                $other_atomics[] = $atomic;
            }
        }

        if ($key_types === [] || $value_types === []) {
            return $value_type;
        }

        $other_atomics[] = new TArray([
            Type::combineUnionTypeArray($key_types, $codebase),
            self::getMergedValueType(Type::combineUnionTypeArray($value_types, $codebase), $codebase),
        ]);

        return TypeCombiner::combine($other_atomics, $codebase);
    }
}
