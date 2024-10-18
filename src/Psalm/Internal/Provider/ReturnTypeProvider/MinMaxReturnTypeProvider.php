<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_filter;
use function array_merge;
use function array_values;
use function assert;
use function count;
use function in_array;
use function max;
use function min;

/**
 * @internal
 */
final class MinMaxReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['min', 'max'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        if (count($call_args) === 0) {
            return null;
        }

        $statements_source = $event->getStatementsSource();
        $nodeTypeProvider = $statements_source->getNodeTypeProvider();
        $codebase = $statements_source->getCodebase();

        if (count($call_args) === 1
            && ($array_arg_type = $nodeTypeProvider->getType($call_args[0]->value))
            && $array_arg_type->isArray()
        ) {
            return $array_arg_type->getArrayValueType($codebase);
        }

        $all_int = true;
        $min_bounds = [];
        $max_bounds = [];
        foreach ($call_args as $arg) {
            if ($arg_type = $nodeTypeProvider->getType($arg->value)) {
                if ($arg->unpack) {
                    if (!$arg_type->isSingle() || !$arg_type->isArray()) {
                        return Type::getMixed();
                    } else {
                        $possibly_unpacked_arg_types = [];
                        foreach ($arg_type->getArrays() as $t) {
                            if ($t instanceof TKeyedArray) {
                                $possibly_unpacked_arg_types = array_merge(
                                    $possibly_unpacked_arg_types,
                                    array_values($t->properties),
                                );
                            } elseif ($t instanceof TArray) {
                                $possibly_unpacked_arg_types []= $t->type_params[1];
                            } elseif ($t instanceof TClassStringMap && $t->as_type) {
                                $possibly_unpacked_arg_types []= $t->getGenericValueType();
                            }
                        }
                    }
                } else {
                    $possibly_unpacked_arg_types = [$arg_type];
                }
                foreach ($possibly_unpacked_arg_types as $possibly_unpacked_arg_type) {
                    foreach ($possibly_unpacked_arg_type->getAtomicTypes() as $atomic_type) {
                        if (!$atomic_type instanceof TInt) {
                            $all_int = false;
                            break 2;
                        }

                        if ($atomic_type instanceof TLiteralInt) {
                            $min_bounds[] = $atomic_type->value;
                            $max_bounds[] = $atomic_type->value;
                        } elseif ($atomic_type instanceof TIntRange) {
                            $min_bounds[] = $atomic_type->min_bound;
                            $max_bounds[] = $atomic_type->max_bound;
                        } elseif ($atomic_type::class === TInt::class) {
                            $min_bounds[] = null;
                            $max_bounds[] = null;
                        } else {
                            throw new UnexpectedValueException('Unexpected type');
                        }
                    }
                }
            } else {
                return Type::getMixed();
            }
        }

        if ($all_int) {
            if ($event->getFunctionId() === 'min') {
                assert(count($min_bounds) !== 0);
                //null values in $max_bounds doesn't make sense for min() so we remove them
                $max_bounds = array_filter($max_bounds, static fn($v): bool => $v !== null) ?: [null];

                $min_potential_int = in_array(null, $min_bounds, true) ? null : min($min_bounds);
                $max_potential_int = in_array(null, $max_bounds, true) ? null : min($max_bounds);
            } else {
                assert(count($max_bounds) !== 0);
                //null values in $min_bounds doesn't make sense for max() so we remove them
                $min_bounds = array_filter($min_bounds, static fn($v): bool => $v !== null) ?: [null];

                $min_potential_int = in_array(null, $min_bounds, true) ? null : max($min_bounds);
                $max_potential_int = in_array(null, $max_bounds, true) ? null : max($max_bounds);
            }

            if ($min_potential_int === null && $max_potential_int === null) {
                return Type::getInt();
            }

            if ($min_potential_int === $max_potential_int) {
                return Type::getInt(false, $min_potential_int);
            }

            return Type::getIntRange($min_potential_int, $max_potential_int);
        }

        //if we're dealing with non-int elements, just combine them all together
        $return_type = null;
        foreach ($call_args as $arg) {
            if ($arg_type = $nodeTypeProvider->getType($arg->value)) {
                if ($arg->unpack) {
                    if ($arg_type->isSingle() && $arg_type->isArray()) {
                        $additional_type = Type::combineUnionTypeArray($arg_type->getArrayValueTypes());
                    } else {
                        $additional_type = Type::getMixed();
                    }
                } else {
                    $additional_type = $arg_type;
                }
                $return_type = Type::combineUnionTypes(
                    $return_type,
                    $additional_type,
                );
            } else {
                return Type::getMixed();
            }
        }

        return $return_type;
    }
}
