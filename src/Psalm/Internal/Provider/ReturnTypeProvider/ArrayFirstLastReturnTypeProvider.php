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

use function array_reverse;
use function array_shift;
use function array_values;

/**
 * array_first() and array_last() (PHP 8.5) return a value of the array, or null when it is empty.
 *
 * For a list this is the type of its first or last element. A shape doesn't fix the order of its keys,
 * so for other arrays it is their value type, as with reset() and end().
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

        $codebase = $statements_source->getCodebase();

        // before PHP 8.5, array_first() and array_last() can only be user-defined functions, e.g. polyfills
        if ($codebase->analysis_php_version_id < 8_05_00) {
            return null;
        }

        $array_type = $statements_source->node_data->getType($array_arg->value);

        if (!$array_type) {
            return null;
        }

        $atomic_types = $array_type->getAtomicTypes();
        $value_type = null;
        $possibly_empty = false;

        while ($atomic_type = array_shift($atomic_types)) {
            if ($atomic_type instanceof TTemplateParam) {
                // bound atomics share keys such as "array", so append them as a list
                $atomic_types = [...$atomic_types, ...array_values($atomic_type->as->getAtomicTypes())];
                continue;
            }

            if ($atomic_type instanceof TArray) {
                $atomic_value_type = $atomic_type->type_params[1];
                $possibly_empty = $possibly_empty || !$atomic_type instanceof TNonEmptyArray;
            } elseif ($atomic_type instanceof TKeyedArray) {
                if (!$atomic_type->is_list || !isset($atomic_type->properties[0])) {
                    // a shape doesn't fix the order of its keys
                    $atomic_value_type = $atomic_type->getGenericValueType();
                } elseif ($event->getFunctionId() === 'array_first') {
                    $atomic_value_type = $atomic_type->properties[0]->setPossiblyUndefined(false);
                } else {
                    $atomic_value_type = self::getLastListElementType($atomic_type);
                }

                $possibly_empty = $possibly_empty || !$atomic_type->isNonEmpty();
            } elseif ($atomic_type instanceof TNull || $atomic_type instanceof TFalse) {
                // throws a TypeError, reported by the callmap signature
                continue;
            } else {
                // e.g. mixed, iterable or class-string-map: keep the callmap signature
                return null;
            }

            $value_type = Type::combineUnionTypes($value_type, $atomic_value_type);
        }

        if (!$value_type) {
            return null;
        }

        if ($value_type->isNever()) {
            $value_type = Type::getNull();
        } elseif ($possibly_empty) {
            $value_type = $value_type->getBuilder()->addType(new TNull());

            if ($codebase->config->ignore_internal_nullable_issues) {
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

    private static function getLastListElementType(TKeyedArray $list): Union
    {
        $last_type = $list->fallback_params[1] ?? Type::getNever();

        // optional list elements follow the required ones: the last element is the last required one or any after it
        foreach (array_reverse($list->properties) as $element_type) {
            $last_type = Type::combineUnionTypes($element_type->setPossiblyUndefined(false), $last_type);

            if (!$element_type->possibly_undefined) {
                break;
            }
        }

        return $last_type;
    }
}
