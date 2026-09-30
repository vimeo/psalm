<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Union;

use function array_values;
use function count;

/**
 * Infers the return type of array_first() and array_last() (PHP 8.5+).
 *
 * Neither function modifies its argument, so unlike array_shift() and array_pop()
 * there is nothing to do with the passed array itself.
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
        $call_args = $event->getCallArgs();

        if (!$statements_source instanceof StatementsAnalyzer || count($call_args) !== 1) {
            return null;
        }

        $first_arg_type = $statements_source->node_data->getType($call_args[0]->value);

        if (!$first_arg_type || $first_arg_type->hasMixed()) {
            return null;
        }

        $is_first = $event->getFunctionId() === 'array_first';
        $return_type = null;
        $nullable = false;

        foreach ($first_arg_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TArray) {
                if ($atomic_type->isEmptyArray()) {
                    $nullable = true;
                    continue;
                }

                $value_type = $atomic_type->type_params[1];

                if (!$atomic_type instanceof TNonEmptyArray) {
                    $nullable = true;
                }
            } elseif ($atomic_type instanceof TKeyedArray) {
                [$value_type, $possibly_empty] = self::getKeyedArrayValueType($atomic_type, $is_first);

                if ($possibly_empty) {
                    $nullable = true;
                }
            } else {
                // Not an array we understand (e.g. a template or iterable), use the callmap signature
                return null;
            }

            $return_type = Type::combineUnionTypes($return_type, $value_type);
        }

        if ($return_type === null) {
            return $nullable ? Type::getNull() : null;
        }

        if (!$nullable) {
            return $return_type;
        }

        $return_type = $return_type->getBuilder()->addType(Type::getNull()->getSingleAtomic());

        if ($statements_source->getCodebase()->config->ignore_internal_nullable_issues) {
            $return_type->ignore_nullable_issues = true;
        }

        return $return_type->freeze();
    }

    /**
     * Only lists have a known key order: the key order of other shapes is not
     * guaranteed, so for them every value is a candidate.
     *
     * @return array{Union, bool} the value type, and whether the array may be empty
     */
    private static function getKeyedArrayValueType(TKeyedArray $array, bool $is_first): array
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
