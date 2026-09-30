<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use PhpParser\Node\Arg;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_shift;
use function array_values;
use function count;

/**
 * Infers the return type of array_first() and array_last() (PHP 8.5+).
 *
 * The inference is shared with array_shift() and array_pop(), which return the same
 * element. Neither array_first() nor array_last() modifies its argument.
 *
 * @internal
 */
final class ArrayFirstLastReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['array_first', 'array_last'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();

        if (!$statements_source instanceof StatementsAnalyzer) {
            return null;
        }

        return self::getElementType(
            $statements_source,
            $event->getCallArgs(),
            $event->getFunctionId() === 'array_first',
        );
    }

    /**
     * Returns the type of the first or last element of the array passed as the only argument,
     * or null when it cannot be inferred.
     *
     * @param list<Arg> $call_args
     */
    public static function getElementType(
        StatementsAnalyzer $statements_analyzer,
        array $call_args,
        bool $is_first,
    ): ?Union {
        $array_arg_type = self::getArrayArgType($statements_analyzer, $call_args);

        if (!$array_arg_type) {
            return null;
        }

        // numeric keys, so that template bounds appended below don't overwrite pending types
        $atomic_types = array_values($array_arg_type->getAtomicTypes());
        $return_type = null;
        $has_array = false;
        $nullable = false;

        while ($atomic_type = array_shift($atomic_types)) {
            if ($atomic_type instanceof TTemplateParam) {
                $atomic_types = [...$atomic_types, ...array_values($atomic_type->as->getAtomicTypes())];
                continue;
            }

            if ($atomic_type instanceof TMixed) {
                return null;
            }

            if ($atomic_type instanceof TArray) {
                $has_array = true;

                if ($atomic_type->isEmptyArray()) {
                    $nullable = true;
                    continue;
                }

                $value_type = $atomic_type->type_params[1];

                if (!$atomic_type instanceof TNonEmptyArray) {
                    $nullable = true;
                }
            } elseif ($atomic_type instanceof TKeyedArray) {
                $has_array = true;

                [$value_type, $possibly_empty] = self::getKeyedArrayElementType($atomic_type, $is_first);

                if ($possibly_empty) {
                    $nullable = true;
                }
            } else {
                // Any other type (e.g. iterable or null) makes the call throw a TypeError,
                // which the callmap signature reports as an invalid argument
                continue;
            }

            $return_type = Type::combineUnionTypes($return_type, $value_type);
        }

        if (!$has_array) {
            return null;
        }

        if ($return_type === null) {
            return Type::getNull();
        }

        if (!$nullable) {
            return $return_type;
        }

        $return_type = $return_type->getBuilder()->addType(Type::getNull()->getSingleAtomic());

        if ($statements_analyzer->getCodebase()->config->ignore_internal_nullable_issues) {
            $return_type->ignore_nullable_issues = true;
        }

        return $return_type->freeze();
    }

    /**
     * @param list<Arg> $call_args
     */
    private static function getArrayArgType(StatementsAnalyzer $statements_analyzer, array $call_args): ?Union
    {
        if (count($call_args) !== 1) {
            return null;
        }

        $arg_type = $statements_analyzer->node_data->getType($call_args[0]->value);

        if (!$arg_type || !$call_args[0]->unpack) {
            return $arg_type;
        }

        // With f(...$args), the array argument is the first element of $args, or its "array" key
        if (!$arg_type->isSingle()) {
            return null;
        }

        $unpacked = $arg_type->getSingleAtomic();

        if (!$unpacked instanceof TKeyedArray) {
            return null;
        }

        $array_arg_type = $unpacked->properties[0] ?? $unpacked->properties['array'] ?? null;

        if (!$array_arg_type || $array_arg_type->possibly_undefined) {
            return null;
        }

        return $array_arg_type;
    }

    /**
     * Only lists have a known key order: the key order of other shapes is not
     * guaranteed, so for them every value is a candidate.
     *
     * @return array{Union, bool} the element type, and whether the array may be empty
     */
    private static function getKeyedArrayElementType(TKeyedArray $array, bool $is_first): array
    {
        $possibly_empty = !$array->isNonEmpty();

        if (!$array->is_list) {
            return [$array->getGenericValueType(), $possibly_empty];
        }

        // In a list, optional elements are always trailing and keys are sorted
        $properties = array_values($array->properties);

        if ($is_first) {
            return [$properties[0]->setPossiblyUndefined(false), $possibly_empty];
        }

        if ($array->fallback_params !== null) {
            return [$array->getGenericValueType(), $possibly_empty];
        }

        // The last element is either the last required one or any optional one after it
        $value_type = null;

        for ($i = count($properties) - 1; $i >= 0; $i--) {
            $value_type = Type::combineUnionTypes(
                $value_type,
                $properties[$i]->setPossiblyUndefined(false),
            );

            if (!$properties[$i]->possibly_undefined) {
                break;
            }
        }

        return [$value_type ?? $array->getGenericValueType(), $possibly_empty];
    }
}
