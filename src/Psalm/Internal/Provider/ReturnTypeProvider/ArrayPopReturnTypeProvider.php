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
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;

use function array_key_last;
use function is_int;

/**
 * @internal
 */
final class ArrayPopReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['array_pop', 'array_shift'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $function_id = $event->getFunctionId();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        $first_arg = $call_args[0]->value ?? null;

        $first_arg_array = $first_arg
            && ($first_arg_type = $statements_source->node_data->getType($first_arg))
            && $first_arg_type->hasType('array')
            && !$first_arg_type->hasMixed()
            && ($array_atomic_type = $first_arg_type->getArray())
            && ($array_atomic_type instanceof TArray
                || $array_atomic_type instanceof TKeyedArray)
        ? $array_atomic_type
        : null;

        if (!$first_arg_array) {
            return Type::getMixed();
        }

        $nullable = false;

        if ($first_arg_array instanceof TArray) {
            $value_type = $first_arg_array->type_params[1];

            if ($first_arg_array->isEmptyArray()) {
                return Type::getNull();
            }

            if (!$first_arg_array instanceof TNonEmptyArray) {
                $nullable = true;
            }
        } else {
            // special case for array_shift with lists
            if ($function_id === 'array_shift' && $first_arg_array->is_list && isset($first_arg_array->properties[0])) {
                $value_type = $first_arg_array->properties[0];
                if ($value_type->possibly_undefined) {
                    $value_type = $value_type->setPossiblyUndefined(false);
                    $nullable = true;
                }
            } elseif ($function_id === 'array_pop' && $first_arg_array->is_list) {
                // Handle keyed list
                $properties = $first_arg_array->properties;

                $last_key = array_key_last($properties);
                $last_value = $properties[$last_key];

                if (!$last_value->possibly_undefined) {
                    // Last key is not optional - return its type
                    $value_type = $last_value;
                } else {
                    // Last key is optional
                    // Find the last non-optional key and collect all types from there onwards
                    $last_non_optional_key = null;
                    if (is_int($last_key)) {
                        for ($i = $last_key - 1; $i >= 0; $i--) {
                            if (isset($properties[$i]) && !$properties[$i]->possibly_undefined) {
                                $last_non_optional_key = $i;
                                break;
                            }
                        }
                    }

                    // Collect all types from last non-optional onwards (or all if none are non-optional)
                    $types_to_combine = [];
                    $start_index = $last_non_optional_key ?? 0;

                    if (is_int($last_key) && is_int($start_index)) {
                        for ($i = $start_index; $i <= $last_key; $i++) {
                            if (isset($properties[$i])) {
                                $types_to_combine[] = $properties[$i]->setPossiblyUndefined(false);
                            }
                        }
                    }

                    if ($types_to_combine !== []) {
                        $value_type = Type::combineUnionTypeArray($types_to_combine, null);
                    } else {
                        return Type::getNull();
                    }

                    if ($last_non_optional_key === null) {
                        // All keys are optional - can also be null
                        $nullable = true;
                    }
                }
            } elseif ($function_id === 'array_pop' && !$first_arg_array->is_list) {
                // Regular keyed array (non-list)
                $all_optional = true;
                foreach ($first_arg_array->properties as $property) {
                    if (!$property->possibly_undefined) {
                        $all_optional = false;
                        break;
                    }
                }

                if ($all_optional) {
                    $nullable = true;
                }

                $value_type = $first_arg_array->getGenericValueType();

                if (!$first_arg_array->isNonEmpty()) {
                    $nullable = true;
                }
            } else {
                $value_type = $first_arg_array->getGenericValueType();

                if (!$first_arg_array->isNonEmpty()) {
                    $nullable = true;
                }
            }
        }

        if ($nullable) {
            $value_type = $value_type->getBuilder()->addType(new TNull);

            $codebase = $statements_source->getCodebase();

            if ($codebase->config->ignore_internal_nullable_issues) {
                $value_type->ignore_nullable_issues = true;
            }

            $value_type = $value_type->freeze();
        }

        return $value_type;
    }
}
