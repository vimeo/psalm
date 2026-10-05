<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_shift;
use function array_values;

/**
 * array_first() and array_last() (PHP 8.5) return a value of the array, or null when it is empty.
 *
 * Like reset() and end(), the result is the value type of the array: which element comes first or last
 * is not inferred.
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
        $array_arg = $event->getCallArgs()[0] ?? null;

        if (!$statements_source instanceof StatementsAnalyzer || !$array_arg || $array_arg->unpack) {
            return null;
        }

        $array_type = $statements_source->node_data->getType($array_arg->value);

        if (!$array_type) {
            return null;
        }

        $atomic_types = array_values($array_type->getAtomicTypes());
        $value_type = null;
        $has_array = false;
        $possibly_empty = false;

        while ($atomic_type = array_shift($atomic_types)) {
            if ($atomic_type instanceof TTemplateParam) {
                $atomic_types = [...$atomic_types, ...array_values($atomic_type->as->getAtomicTypes())];
                continue;
            }

            if ($atomic_type instanceof TArray) {
                $atomic_value_type = $atomic_type->type_params[1];
                $possibly_empty = $possibly_empty || !$atomic_type instanceof TNonEmptyArray;
            } elseif ($atomic_type instanceof TKeyedArray) {
                $atomic_value_type = $atomic_type->getGenericValueType();
                $possibly_empty = $possibly_empty || !$atomic_type->isNonEmpty();
            } elseif ($atomic_type instanceof TNull || $atomic_type instanceof TFalse) {
                // throws a TypeError, reported by the callmap signature
                continue;
            } else {
                // e.g. mixed, iterable or class-string-map: keep the callmap signature
                return null;
            }

            $has_array = true;
            $value_type = Type::combineUnionTypes($value_type, $atomic_value_type);
        }

        if (!$has_array || !$value_type) {
            return null;
        }

        if ($value_type->isNever()) {
            $value_type = Type::getNull();
        } elseif ($possibly_empty) {
            $value_type = $value_type->getBuilder()->addType(Type::getNull()->getSingleAtomic());

            if ($statements_source->getCodebase()->config->ignore_internal_nullable_issues) {
                $value_type->ignore_nullable_issues = true;
            }

            $value_type = $value_type->freeze();
        }

        $offset_type = Type::getMixed();
        ArrayFetchAnalyzer::taintArrayFetch(
            $statements_source,
            $array_arg->value,
            null,
            $value_type,
            $offset_type,
        );

        return $value_type;
    }
}
