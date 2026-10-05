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
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function count;

/**
 * array_replace_recursive() returns the keys of all its arrays, each with the value of the last array having it, or
 * the arrays they have under it merged the same way: shapes are merged key by key, other arrays into any array of
 * their keys and values.
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

        $result = null;

        foreach ($call_args as $call_arg) {
            $arg_type = $call_arg->unpack ? null : $statements_source->node_data->getType($call_arg->value);

            if ($arg_type === null || !$arg_type->isArray()) {
                return null;
            }

            $result = $result === null ? $arg_type : self::replaceArrays($result, $arg_type, $codebase);
        }

        return $result;
    }

    /**
     * What replacing the arrays $replaced can be with the arrays $replacements can be gives
     */
    private static function replaceArrays(Union $replaced, Union $replacements, Codebase $codebase): Union
    {
        $atomics = [];

        foreach ($replaced->getAtomicTypes() as $replaced_atomic) {
            foreach ($replacements->getAtomicTypes() as $replacement_atomic) {
                $atomics[] = self::replaceArray($replaced_atomic, $replacement_atomic, $codebase);
            }
        }

        return TypeCombiner::combine($atomics, $codebase);
    }

    private static function replaceArray(Atomic $replaced, Atomic $replacement, Codebase $codebase): Atomic
    {
        if (self::isEmptyArray($replacement)) {
            return $replaced;
        }

        if (self::isEmptyArray($replaced)) {
            return $replacement;
        }

        [$replaced_properties, $replaced_fallback] = self::getShape($replaced);
        [$replacement_properties, $replacement_fallback] = self::getShape($replacement);

        $properties = $replaced_properties;

        foreach ($replacement_properties as $key => $value) {
            $defined_value = $value->setPossiblyUndefined(false);

            if (isset($properties[$key])) {
                $replaced_value = $properties[$key];
                $value_type = self::replaceValue($replaced_value, $defined_value, $codebase);
            } elseif ($replaced_fallback !== null) {
                // the replaced array may have the key, with a value of its other keys
                $replaced_value = $replaced_fallback[1]->setPossiblyUndefined(true);
                $value_type = Type::combineUnionTypes(
                    $defined_value,
                    self::replaceValue($replaced_fallback[1], $defined_value, $codebase),
                    $codebase,
                );
            } else {
                $properties[$key] = $value;

                continue;
            }

            // a key the replacement may not have keeps what it had too
            $properties[$key] = $value->possibly_undefined
                ? Type::combineUnionTypes($replaced_value, $value_type, $codebase)
                : $value_type;
        }

        if ($replacement_fallback !== null) {
            // the replacement may have the keys only the replaced array is known to have, with a value of its others
            foreach ($replaced_properties as $key => $value) {
                if (!isset($replacement_properties[$key])) {
                    $properties[$key] = Type::combineUnionTypes(
                        $value,
                        self::replaceValue($value, $replacement_fallback[1], $codebase),
                        $codebase,
                    );
                }
            }
        }

        $fallback = null;

        if ($replaced_fallback !== null || $replacement_fallback !== null) {
            $fallback_keys = [];
            $fallback_values = [];

            foreach ([$replaced_fallback, $replacement_fallback] as $shape_fallback) {
                if ($shape_fallback !== null) {
                    [$fallback_keys[], $fallback_values[]] = $shape_fallback;
                }
            }

            if ($fallback_keys !== [] && $fallback_values !== []) {
                $fallback = [
                    Type::combineUnionTypeArray($fallback_keys, $codebase),
                    self::getMergedValueType(Type::combineUnionTypeArray($fallback_values, $codebase), $codebase),
                ];
            }
        }

        if ($properties === []) {
            if ($fallback === null) {
                return new TArray([Type::getNever(), Type::getNever()]);
            }

            return self::isNonEmpty($replaced) || self::isNonEmpty($replacement)
                ? new TNonEmptyArray($fallback)
                : new TArray($fallback);
        }

        return TKeyedArray::make(
            $properties,
            null,
            $fallback,
            self::isList($replaced) && self::isList($replacement),
        );
    }

    /**
     * The keys an array is known to have, and the key and value types of its others (null if it has no others)
     *
     * @return array{array<array-key, Union>, ?array{Union, Union}}
     */
    private static function getShape(Atomic $array): array
    {
        if ($array instanceof TKeyedArray) {
            return [$array->properties, $array->fallback_params];
        }

        return [[], self::getGenericParams($array)];
    }

    /**
     * @psalm-pure
     */
    private static function isList(Atomic $array): bool
    {
        return $array instanceof TKeyedArray && $array->is_list;
    }

    /**
     * What replacing a value that can be $replaced with one that is $replacement gives: an array of both is merged
     */
    private static function replaceValue(Union $replaced, Union $replacement, Codebase $codebase): Union
    {
        $replaced_arrays = [];

        foreach ($replaced->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TArray || $atomic instanceof TKeyedArray) {
                $replaced_arrays[] = $atomic;
            }
        }

        $atomics = [];

        foreach ($replacement->getAtomicTypes() as $atomic) {
            if (($atomic instanceof TArray || $atomic instanceof TKeyedArray) && $replaced_arrays !== []) {
                foreach ($replaced_arrays as $replaced_array) {
                    $atomics[] = self::replaceArray($replaced_array, $atomic, $codebase);
                }

                // what is replaced may also not be an array
                if (count($replaced_arrays) !== count($replaced->getAtomicTypes())) {
                    $atomics[] = $atomic;
                }
            } else {
                $atomics[] = $atomic;
            }
        }

        return TypeCombiner::combine($atomics, $codebase);
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
        $all_lists = true;

        foreach ($value_type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TArray || $atomic instanceof TKeyedArray) {
                if (!self::isEmptyArray($atomic)) {
                    [$key_types[], $value_types[]] = self::getGenericParams($atomic);
                    $all_lists = $all_lists && self::isList($atomic);
                }
            } else {
                // a template that can be an array may be merged with another array: that array is any array
                if ($atomic instanceof TTemplateParam && ($atomic->as->hasArray() || $atomic->as->hasMixed())) {
                    $key_types[] = Type::getArrayKey();
                    $value_types[] = Type::getMixed();
                    $all_lists = false;
                }

                $other_atomics[] = $atomic;
            }
        }

        if ($key_types === [] || $value_types === []) {
            return $value_type;
        }

        $merged_value_type = self::getMergedValueType(Type::combineUnionTypeArray($value_types, $codebase), $codebase);

        // lists merged by their indexes are a list
        $other_atomics[] = $all_lists
            ? Type::getListAtomic($merged_value_type)
            : new TArray([Type::combineUnionTypeArray($key_types, $codebase), $merged_value_type]);

        return TypeCombiner::combine($other_atomics, $codebase);
    }

    /**
     * @return array{Union, Union}
     */
    private static function getGenericParams(Atomic $array): array
    {
        if ($array instanceof TKeyedArray) {
            return [$array->getGenericKeyType(), $array->getGenericValueType()];
        }

        if ($array instanceof TArray) {
            return $array->type_params;
        }

        return [Type::getArrayKey(), Type::getMixed()];
    }

    /**
     * @psalm-mutation-free
     */
    private static function isEmptyArray(Atomic $array): bool
    {
        return $array instanceof TArray && $array->isEmptyArray();
    }

    /**
     * @psalm-mutation-free
     */
    private static function isNonEmpty(Atomic $array): bool
    {
        return $array instanceof TNonEmptyArray || ($array instanceof TKeyedArray && $array->isNonEmpty());
    }
}
