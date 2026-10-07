<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Union;

use function assert;

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
     * How many more arrays of an unpacked argument are tried before giving up on a result they all fit
     */
    private const MAX_UNPACKED_REPLACEMENTS = 5;

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
            $arg_type = $statements_source->node_data->getType($call_arg->value);

            if ($arg_type === null || !$arg_type->isArray()) {
                return null;
            }

            if ($call_arg->unpack) {
                $result = self::replaceWithUnpacked($result, $arg_type, $codebase);

                if ($result === null) {
                    return null;
                }
            } else {
                $result = $result === null ? $arg_type : self::replaceArrays($result, $arg_type, $codebase);
            }
        }

        return $result;
    }

    /**
     * What replacing the arrays $replaced can be (null if there are none before) with the arrays unpacked from what
     * $unpacked can be gives, or null if it can't be inferred
     */
    private static function replaceWithUnpacked(?Union $replaced, Union $unpacked, Codebase $codebase): ?Union
    {
        $results = [];

        foreach ($unpacked->getAtomicTypes() as $atomic) {
            $result = self::replaceWithUnpackedArray($replaced, $atomic, $codebase);

            if ($result === null) {
                return null;
            }

            $results[] = $result;
        }

        return Type::combineUnionTypeArray($results, $codebase);
    }

    private static function replaceWithUnpackedArray(?Union $replaced, Atomic $unpacked, Codebase $codebase): ?Union
    {
        if (self::isEmptyArray($unpacked)) {
            return $replaced;
        }

        [$key_type, $value_type] = self::getGenericParams($unpacked);

        // string keys would be named arguments
        if (!$key_type->isInt() || !$value_type->isArray()) {
            return null;
        }

        if ($unpacked instanceof TKeyedArray
            && $unpacked->is_list
            && $unpacked->fallback_params === null
            && $unpacked->getMinCount() === $unpacked->getMaxCount()
        ) {
            // a known number of arrays, replaced one after another
            for ($i = 0; $i < $unpacked->getMaxCount(); $i++) {
                $arg_type = $unpacked->properties[$i];
                $replaced = $replaced === null ? $arg_type : self::replaceArrays($replaced, $arg_type, $codebase);
            }

            return $replaced;
        }

        // any number of arrays of $value_type: a possibly empty unpack may replace nothing (and the first array is
        // always passed, the call fails without one)
        $value_type = $value_type->setPossiblyUndefined(false);

        if ($replaced === null) {
            $result = $value_type;
        } elseif (self::isNonEmpty($unpacked)) {
            $result = self::replaceArrays($replaced, $value_type, $codebase);
        } else {
            $result = $replaced;
        }

        // replacing with one more array until that gives nothing new
        for ($i = 0; $i < self::MAX_UNPACKED_REPLACEMENTS; $i++) {
            $next_result = Type::combineUnionTypes(
                $result,
                self::replaceArrays($result, $value_type, $codebase),
                $codebase,
            );

            if ($next_result->getId() === $result->getId()) {
                return $result;
            }

            $result = $next_result;
        }

        return null;
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

        $fallback_keys = [];
        $fallback_values = [];

        foreach ([$replaced_fallback, $replacement_fallback] as $shape_fallback) {
            if ($shape_fallback !== null) {
                [$fallback_keys[], $fallback_values[]] = $shape_fallback;
            }
        }

        $fallback = $fallback_keys === [] || $fallback_values === [] ? null : [
            Type::combineUnionTypeArray($fallback_keys, $codebase),
            self::getMergedValueType(Type::combineUnionTypeArray($fallback_values, $codebase), $codebase),
        ];

        if ($properties === []) {
            // neither array is a shape, so both have generic params (empty arrays are handled above)
            assert($fallback !== null);

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
        // a key the replaced array may not have, or a value that may not be an array, just takes the replacement
        $can_be_not_array = $replaced->possibly_undefined;

        foreach ($replaced->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TArray || $atomic instanceof TKeyedArray) {
                $replaced_arrays[] = $atomic;

                continue;
            }

            $can_be_not_array = true;

            if (self::canBeArray($atomic, $codebase)) {
                // mixed, iterable, callable or a template: an array with any keys
                $replaced_arrays[] = Type::getArrayAtomic();
            }
        }

        $atomics = [];

        foreach ($replacement->getAtomicTypes() as $atomic) {
            if (($atomic instanceof TArray || $atomic instanceof TKeyedArray) && $replaced_arrays !== []) {
                foreach ($replaced_arrays as $replaced_array) {
                    $atomics[] = self::replaceArray($replaced_array, $atomic, $codebase);
                }

                if ($can_be_not_array) {
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
                // a value that can be an array (iterable, callable or a template) may be merged with another array:
                // that array is any array (mixed already covers it)
                if (!$atomic instanceof TMixed && self::canBeArray($atomic, $codebase)) {
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
     * Whether a type other than TArray and TKeyedArray can be an array
     */
    private static function canBeArray(Atomic $atomic, Codebase $codebase): bool
    {
        return $atomic instanceof TIterable
            // [$object_or_class, $method]
            || $atomic instanceof TCallable
            || AtomicTypeComparator::canBeIdentical($codebase, $atomic, Type::getArrayAtomic());
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
