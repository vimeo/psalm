<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function in_array;
use function strpos;
use function strtolower;

/**
 * @internal
 */
class ObjectComparator
{
    /**
     * @param  TNamedObject|TTemplateParam|TIterable  $input_type_part
     * @param  TNamedObject|TTemplateParam|TIterable  $container_type_part
     */
    public static function isShallowlyContainedBy(
        Codebase $codebase,
        Atomic $input_type_part,
        Atomic $container_type_part,
        bool $allow_interface_equality,
        ?TypeComparisonResult $atomic_comparison_result
    ): bool {
        $intersection_input_types = self::getIntersectionTypes($input_type_part);
        $intersection_container_types = self::getIntersectionTypes($container_type_part);

        foreach ($intersection_container_types as $intersection_container_type) {
            $container_was_static = false;

            if ($intersection_container_type instanceof TIterable) {
                $intersection_container_type_lower = 'iterable';
            } elseif ($intersection_container_type instanceof TObjectWithProperties) {
                $intersection_container_type_lower = 'object';
            } elseif ($intersection_container_type instanceof TTemplateParam) {
                $intersection_container_type_lower = null;
            } else {
                $container_was_static = $intersection_container_type->is_static;

                $intersection_container_type_lower = strtolower(
                    $codebase->classlikes->getUnAliasedName(
                        $intersection_container_type->value,
                    ),
                );
            }

            $any_inputs_contained = false;

            $container_type_is_interface = $intersection_container_type_lower
                && $codebase->interfaceExists($intersection_container_type_lower);

            foreach ($intersection_input_types as $input_type_key => $intersection_input_type) {
                if ($allow_interface_equality
                    && $container_type_is_interface
                    && !isset($intersection_container_types[$input_type_key])
                ) {
                    $any_inputs_contained = true;
                } elseif (self::isIntersectionShallowlyContainedBy(
                    $codebase,
                    $intersection_input_type,
                    $intersection_container_type,
                    $intersection_container_type_lower,
                    $container_was_static,
                    $allow_interface_equality,
                    $atomic_comparison_result,
                )) {
                    $any_inputs_contained = true;
                }
            }

            if (!$any_inputs_contained) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  TNamedObject|TTemplateParam|TIterable  $type_part
     * @return array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties>
     */
    private static function getIntersectionTypes(Atomic $type_part): array
    {
        if (!$type_part->extra_types) {
            if ($type_part instanceof TTemplateParam) {
                $intersection_types = [];

                foreach ($type_part->as->getAtomicTypes() as $as_atomic_type) {
                    // T1 as T2 as object becomes (T1 as object) & (T2 as object)
                    if ($as_atomic_type instanceof TTemplateParam) {
                        $intersection_types += self::getIntersectionTypes($as_atomic_type);
                        $type_part = $type_part->replaceAs($as_atomic_type->as);
                        $intersection_types[$type_part->getKey()] = $type_part;

                        return $intersection_types;
                    }
                }
            }

            return [$type_part->getKey() => $type_part];
        }

        $extra_types = $type_part->extra_types;
        $type_part = $type_part->setIntersectionTypes([]);

        $extra_types[$type_part->getKey()] = $type_part;

        return $extra_types;
    }

    /**
     * @param  TNamedObject|TTemplateParam|TIterable|TObjectWithProperties  $intersection_input_type
     * @param  TNamedObject|TTemplateParam|TIterable|TObjectWithProperties  $intersection_container_type
     */
    private static function isIntersectionShallowlyContainedBy(
        Codebase $codebase,
        Atomic $intersection_input_type,
        Atomic $intersection_container_type,
        ?string $intersection_container_type_lower,
        bool $container_was_static,
        bool $allow_interface_equality,
        ?TypeComparisonResult $atomic_comparison_result
    ): bool {
        if ($intersection_container_type instanceof TTemplateParam
            && $intersection_input_type instanceof TTemplateParam
        ) {
            if (!$allow_interface_equality) {
                if (strpos($intersection_container_type->defining_class, 'fn-') === 0
                    || strpos($intersection_input_type->defining_class, 'fn-') === 0
                ) {
                    if (strpos($intersection_input_type->defining_class, 'fn-') === 0
                        && strpos($intersection_container_type->defining_class, 'fn-') === 0
                        && $intersection_input_type->defining_class
                            !== $intersection_container_type->defining_class
                    ) {
                        return true;
                    }

                    foreach ($intersection_input_type->as->getAtomicTypes() as $input_as_atomic) {
                        if ($input_as_atomic->equals($intersection_container_type, false)) {
                            return true;
                        }
                    }
                }
            }

            if ($intersection_container_type->param_name === $intersection_input_type->param_name
                && $intersection_container_type->defining_class === $intersection_input_type->defining_class
            ) {
                return true;
            }

            if ($intersection_container_type->param_name !== $intersection_input_type->param_name
                || ($intersection_container_type->defining_class
                    !== $intersection_input_type->defining_class
                    && strpos($intersection_input_type->defining_class, 'fn-') !== 0
                    && strpos($intersection_container_type->defining_class, 'fn-') !== 0)
            ) {
                if (strpos($intersection_input_type->defining_class, 'fn-') === 0
                    || strpos($intersection_container_type->defining_class, 'fn-') === 0
                ) {
                    return false;
                }

                $input_class_storage = $codebase->classlike_storage_provider->get(
                    $intersection_input_type->defining_class,
                );

                if (isset($input_class_storage->template_extended_params
                        [$intersection_container_type->defining_class]
                        [$intersection_container_type->param_name])
                ) {
                    return true;
                }
            }

            return false;
        }

        if ($intersection_container_type instanceof TTemplateParam
            || $intersection_container_type_lower === null
        ) {
            return false;
        }

        if ($intersection_input_type instanceof TTemplateParam) {
            if ($intersection_container_type instanceof TNamedObject && $intersection_container_type->is_static) {
                // this is extra check is redundant since we're comparing to a template as type
                $intersection_container_type = new TNamedObject(
                    $intersection_container_type->value,
                    false,
                    $intersection_container_type->definite_class,
                    $intersection_container_type->extra_types,
                );
            }

            return UnionTypeComparator::isContainedBy(
                $codebase,
                $intersection_input_type->as,
                new Union([$intersection_container_type]),
                false,
                false,
                $atomic_comparison_result,
                $allow_interface_equality,
            );
        }

        $input_was_static = false;

        if ($intersection_input_type instanceof TIterable) {
            $intersection_input_type_lower = 'iterable';
        } elseif ($intersection_input_type instanceof TObjectWithProperties) {
            $intersection_input_type_lower = 'object';
        } else {
            $input_was_static = $intersection_input_type->is_static;

            $intersection_input_type_lower = strtolower(
                $codebase->classlikes->getUnAliasedName(
                    $intersection_input_type->value,
                ),
            );
        }

        if ($intersection_container_type_lower === $intersection_input_type_lower) {
            if ($container_was_static && !$input_was_static) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }

                return false;
            }

            return true;
        }

        if ($intersection_input_type_lower === 'generator'
            && in_array($intersection_container_type_lower, ['iterator', 'traversable', 'iterable'], true)
        ) {
            return true;
        }

        if ($intersection_container_type_lower === 'iterable') {
            if ($intersection_input_type_lower === 'traversable'
                || ($codebase->classlikes->classExists($intersection_input_type_lower)
                    && $codebase->classlikes->classImplements(
                        $intersection_input_type_lower,
                        'Traversable',
                    ))
                || ($codebase->classlikes->interfaceExists($intersection_input_type_lower)
                    && $codebase->classlikes->interfaceExtends(
                        $intersection_input_type_lower,
                        'Traversable',
                    ))
            ) {
                return true;
            }
        }

        if ($intersection_input_type_lower === 'traversable'
            && $intersection_container_type_lower === 'iterable'
        ) {
            return true;
        }

        $input_type_is_interface = $codebase->interfaceExists($intersection_input_type_lower);
        $container_type_is_interface = $codebase->interfaceExists($intersection_container_type_lower);

        if ($allow_interface_equality
            && $container_type_is_interface
            && $input_type_is_interface
        ) {
            return true;
        }

        if (($codebase->classExists($intersection_input_type_lower)
                || $codebase->classlikes->enumExists($intersection_input_type_lower))
            && $codebase->classOrInterfaceExists($intersection_container_type_lower)
            && $codebase->classExtendsOrImplements(
                $intersection_input_type_lower,
                $intersection_container_type_lower,
            )
        ) {
            if ($container_was_static && !$input_was_static) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }

                return false;
            }

            return true;
        }

        if ($input_type_is_interface
            && $codebase->interfaceExtends(
                $intersection_input_type_lower,
                $intersection_container_type_lower,
            )
        ) {
            return true;
        }

        if (ExpressionAnalyzer::isMock($intersection_input_type_lower)) {
            return true;
        }

        return false;
    }
}
