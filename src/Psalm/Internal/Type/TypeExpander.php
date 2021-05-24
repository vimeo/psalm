<?php
namespace Psalm\Internal\Type;

use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TNamedObject;
use function strpos;
use function is_string;
use function strtolower;
use function count;
use function is_array;
use function array_merge;
use function array_values;
use function reset;

/**
 * @internal
 */
class TypeExpander
{
    /**
     * @param  string|Type\Atomic\TNamedObject|Type\Atomic\TTemplateParam|null $static_class_type
     *
     */
    public static function expandUnion(
        Codebase $codebase,
        Type\Union $return_type,
        ?string $self_class,
        $static_class_type,
        ?string $parent_class,
        bool $evaluate_class_constants = true,
        bool $evaluate_conditional_types = false,
        bool $final = false,
        bool $expand_generic = false,
        bool $expand_templates = false
    ): Type\Union {
        $return_type = clone $return_type;

        $new_return_type_parts = [];

        $has_array_output = false;

        foreach ($return_type->getAtomicTypes() as $return_type_part) {
            $parts = self::expandAtomic(
                $codebase,
                $return_type_part,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate_class_constants,
                $evaluate_conditional_types,
                $final,
                $expand_generic,
                $expand_templates
            );

            if (is_array($parts)) {
                $new_return_type_parts = array_merge($new_return_type_parts, $parts);
                $has_array_output = true;
            } else {
                $new_return_type_parts[] = $parts;
            }
        }

        if ($has_array_output) {
            $fleshed_out_type = TypeCombiner::combine(
                $new_return_type_parts,
                $codebase
            );
        } else {
            $fleshed_out_type = new Type\Union($new_return_type_parts);
        }

        $fleshed_out_type->from_docblock = $return_type->from_docblock;
        $fleshed_out_type->ignore_nullable_issues = $return_type->ignore_nullable_issues;
        $fleshed_out_type->ignore_falsable_issues = $return_type->ignore_falsable_issues;
        $fleshed_out_type->possibly_undefined = $return_type->possibly_undefined;
        $fleshed_out_type->possibly_undefined_from_try = $return_type->possibly_undefined_from_try;
        $fleshed_out_type->by_ref = $return_type->by_ref;
        $fleshed_out_type->initialized = $return_type->initialized;
        $fleshed_out_type->from_property = $return_type->from_property;
        $fleshed_out_type->from_static_property = $return_type->from_static_property;
        $fleshed_out_type->had_template = $return_type->had_template;
        $fleshed_out_type->parent_nodes = $return_type->parent_nodes;

        return $fleshed_out_type;
    }

    /**
     * @param  string|Type\Atomic\TNamedObject|Type\Atomic\TTemplateParam|null $static_class_type
     *
     * @return Type\Atomic|non-empty-list<Type\Atomic>
     */
    public static function expandAtomic(
        Codebase $codebase,
        Type\Atomic &$return_type,
        ?string $self_class,
        $static_class_type,
        ?string $parent_class,
        bool $evaluate_class_constants = true,
        bool $evaluate_conditional_types = false,
        bool $final = false,
        bool $expand_generic = false,
        bool $expand_templates = false
    ) {
        if ($return_type instanceof TNamedObject
            || $return_type instanceof TTemplateParam
        ) {
            if ($return_type->extra_types) {
                $new_intersection_types = [];

                foreach ($return_type->extra_types as &$extra_type) {
                    self::expandAtomic(
                        $codebase,
                        $extra_type,
                        $self_class,
                        $static_class_type,
                        $parent_class,
                        $evaluate_class_constants,
                        $evaluate_conditional_types,
                        $expand_generic,
                        $expand_templates
                    );

                    if ($extra_type instanceof TNamedObject && $extra_type->extra_types) {
                        $new_intersection_types = array_merge(
                            $new_intersection_types,
                            $extra_type->extra_types
                        );
                        $extra_type->extra_types = [];
                    }
                }

                if ($new_intersection_types) {
                    $return_type->extra_types = array_merge($return_type->extra_types, $new_intersection_types);
                }
            }

            if ($return_type instanceof TNamedObject) {
                $return_type = self::expandNamedObject(
                    $codebase,
                    $return_type,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $final,
                    $expand_generic
                );
            }
        }

        if ($return_type instanceof Type\Atomic\TClassString
            && $return_type->as_type
        ) {
            $new_as_type = clone $return_type->as_type;

            self::expandAtomic(
                $codebase,
                $new_as_type,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate_class_constants,
                $evaluate_conditional_types,
                $final,
                $expand_generic,
                $expand_templates
            );

            if ($new_as_type instanceof TNamedObject) {
                $return_type->as_type = $new_as_type;
                $return_type->as = $return_type->as_type->value;
            }
        } elseif ($return_type instanceof Type\Atomic\TTemplateParam) {
            $new_as_type = self::expandUnion(
                $codebase,
                clone $return_type->as,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate_class_constants,
                $evaluate_conditional_types,
                $final,
                $expand_generic,
                $expand_templates
            );

            if ($expand_templates) {
                return array_values($new_as_type->getAtomicTypes());
            }

            $return_type->as = $new_as_type;
        }

        if ($return_type instanceof Type\Atomic\TClassConstant) {
            if ($return_type->fq_classlike_name === 'self' && $self_class) {
                $return_type->fq_classlike_name = $self_class;
            }

            if ($return_type->fq_classlike_name === 'static' && $self_class) {
                $return_type->fq_classlike_name = is_string($static_class_type) ? $static_class_type : $self_class;
            }

            if ($evaluate_class_constants && $codebase->classOrInterfaceOrEnumExists($return_type->fq_classlike_name)) {
                if (strtolower($return_type->const_name) === 'class') {
                    return new Type\Atomic\TLiteralClassString($return_type->fq_classlike_name);
                }

                $class_storage = $codebase->classlike_storage_provider->get($return_type->fq_classlike_name);

                if (strpos($return_type->const_name, '*') !== false) {
                    $class_storage = $codebase->classlike_storage_provider->get($return_type->fq_classlike_name);

                    $matching_constants = \array_keys($class_storage->constants);

                    $const_name_part = \substr($return_type->const_name, 0, -1);

                    if ($const_name_part) {
                        $matching_constants = \array_filter(
                            $matching_constants,
                            function ($constant_name) use ($const_name_part): bool {
                                return $constant_name !== $const_name_part
                                    && \strpos($constant_name, $const_name_part) === 0;
                            }
                        );
                    }
                } else {
                    if ($class_storage->is_enum) {
                        return new Type\Atomic\TEnumCase($return_type->fq_classlike_name, $return_type->const_name);
                    }

                    $matching_constants = [$return_type->const_name];
                }

                $matching_constant_types = [];

                foreach ($matching_constants as $matching_constant) {
                    try {
                        $class_constant = $codebase->classlikes->getClassConstantType(
                            $return_type->fq_classlike_name,
                            $matching_constant,
                            \ReflectionProperty::IS_PRIVATE
                        );
                    } catch (\Psalm\Exception\CircularReferenceException $e) {
                        $class_constant = null;
                    }

                    if ($class_constant) {
                        if ($class_constant->isSingle()) {
                            $class_constant = clone $class_constant;

                            $matching_constant_types = \array_merge(
                                \array_values($class_constant->getAtomicTypes()),
                                $matching_constant_types
                            );
                        }
                    }
                }

                if ($matching_constant_types) {
                    return $matching_constant_types;
                }
            }

            return $return_type;
        }

        if ($return_type instanceof Type\Atomic\TTypeAlias) {
            $declaring_fq_classlike_name = $return_type->declaring_fq_classlike_name;

            if ($declaring_fq_classlike_name === 'self' && $self_class) {
                $declaring_fq_classlike_name = $self_class;
            }

            if ($evaluate_class_constants && $codebase->classOrInterfaceExists($declaring_fq_classlike_name)) {
                $class_storage = $codebase->classlike_storage_provider->get($declaring_fq_classlike_name);

                $type_alias_name = $return_type->alias_name;

                if (isset($class_storage->type_aliases[$type_alias_name])) {
                    $resolved_type_alias = $class_storage->type_aliases[$type_alias_name];

                    if ($resolved_type_alias->replacement_atomic_types) {
                        $replacement_atomic_types = $resolved_type_alias->replacement_atomic_types;

                        $recursively_fleshed_out_types = [];

                        foreach ($replacement_atomic_types as $replacement_atomic_type) {
                            $recursively_fleshed_out_type = self::expandAtomic(
                                $codebase,
                                $replacement_atomic_type,
                                $self_class,
                                $static_class_type,
                                $parent_class,
                                $evaluate_class_constants,
                                $evaluate_conditional_types,
                                $final,
                                $expand_generic,
                                $expand_templates
                            );

                            if (is_array($recursively_fleshed_out_type)) {
                                $recursively_fleshed_out_types = array_merge(
                                    $recursively_fleshed_out_type,
                                    $recursively_fleshed_out_types
                                );
                            } else {
                                $recursively_fleshed_out_types[] = $recursively_fleshed_out_type;
                            }
                        }

                        return $recursively_fleshed_out_types;
                    }
                }
            }

            return $return_type;
        }

        if ($return_type instanceof Type\Atomic\TKeyOfClassConstant
            || $return_type instanceof Type\Atomic\TValueOfClassConstant
        ) {
            if ($return_type->fq_classlike_name === 'self' && $self_class) {
                $return_type->fq_classlike_name = $self_class;
            }

            if ($evaluate_class_constants && $codebase->classOrInterfaceExists($return_type->fq_classlike_name)) {
                try {
                    $class_constant_type = $codebase->classlikes->getClassConstantType(
                        $return_type->fq_classlike_name,
                        $return_type->const_name,
                        \ReflectionProperty::IS_PRIVATE
                    );
                } catch (\Psalm\Exception\CircularReferenceException $e) {
                    $class_constant_type = null;
                }

                if ($class_constant_type) {
                    foreach ($class_constant_type->getAtomicTypes() as $const_type_atomic) {
                        if ($const_type_atomic instanceof Type\Atomic\TKeyedArray
                            || $const_type_atomic instanceof Type\Atomic\TArray
                        ) {
                            if ($const_type_atomic instanceof Type\Atomic\TKeyedArray) {
                                $const_type_atomic = $const_type_atomic->getGenericArrayType();
                            }

                            if ($return_type instanceof Type\Atomic\TKeyOfClassConstant) {
                                return array_values($const_type_atomic->type_params[0]->getAtomicTypes());
                            }

                            return array_values($const_type_atomic->type_params[1]->getAtomicTypes());
                        }
                    }
                }
            }

            return $return_type;
        }

        if ($return_type instanceof Type\Atomic\TIntMask) {
            if (!$evaluate_class_constants) {
                return new Type\Atomic\TInt();
            }

            $potential_ints = [];

            foreach ($return_type->values as $value_type) {
                $new_value_type = self::expandAtomic(
                    $codebase,
                    $value_type,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates
                );

                if (\is_array($new_value_type)) {
                    $new_value_type = reset($new_value_type);
                }

                if (!$new_value_type instanceof Type\Atomic\TLiteralInt) {
                    return new Type\Atomic\TInt();
                }

                $potential_ints[] = $new_value_type->value;
            }

            return \Psalm\Internal\Type\TypeParser::getComputedIntsFromMask($potential_ints);
        }

        if ($return_type instanceof Type\Atomic\TIntMaskOf) {
            if (!$evaluate_class_constants) {
                return new Type\Atomic\TInt();
            }

            $value_type = $return_type->value;

            $new_value_types = self::expandAtomic(
                $codebase,
                $value_type,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate_class_constants,
                $evaluate_conditional_types,
                $final,
                $expand_generic,
                $expand_templates
            );

            if (!is_array($new_value_types)) {
                return new Type\Atomic\TInt();
            }

            $potential_ints = [];

            foreach ($new_value_types as $new_value_type) {
                if (!$new_value_type instanceof Type\Atomic\TLiteralInt) {
                    return new Type\Atomic\TInt();
                }

                $potential_ints[] = $new_value_type->value;
            }

            return \Psalm\Internal\Type\TypeParser::getComputedIntsFromMask($potential_ints);
        }

        if ($return_type instanceof Type\Atomic\TArray
            || $return_type instanceof Type\Atomic\TGenericObject
            || $return_type instanceof Type\Atomic\TIterable
        ) {
            foreach ($return_type->type_params as $k => $type_param) {
                /** @psalm-suppress PropertyTypeCoercion */
                $return_type->type_params[$k] = self::expandUnion(
                    $codebase,
                    $type_param,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates
                );
            }
        } elseif ($return_type instanceof Type\Atomic\TKeyedArray) {
            foreach ($return_type->properties as &$property_type) {
                $property_type = self::expandUnion(
                    $codebase,
                    $property_type,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates
                );
            }
        } elseif ($return_type instanceof Type\Atomic\TList) {
            $return_type->type_param = self::expandUnion(
                $codebase,
                $return_type->type_param,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate_class_constants,
                $evaluate_conditional_types,
                $final,
                $expand_generic,
                $expand_templates
            );
        }

        if ($return_type instanceof Type\Atomic\TObjectWithProperties) {
            foreach ($return_type->properties as &$property_type) {
                $property_type = self::expandUnion(
                    $codebase,
                    $property_type,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates
                );
            }
        }

        if ($return_type instanceof Type\Atomic\TCallable
            || $return_type instanceof Type\Atomic\TClosure
        ) {
            if ($return_type->params) {
                foreach ($return_type->params as $param) {
                    if ($param->type) {
                        $param->type = self::expandUnion(
                            $codebase,
                            $param->type,
                            $self_class,
                            $static_class_type,
                            $parent_class,
                            $evaluate_class_constants,
                            $evaluate_conditional_types,
                            $final,
                            $expand_generic,
                            $expand_templates
                        );
                    }
                }
            }
            if ($return_type->return_type) {
                $return_type->return_type = self::expandUnion(
                    $codebase,
                    $return_type->return_type,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates
                );
            }
        }

        if ($return_type instanceof Type\Atomic\TConditional) {
            return self::expandConditional(
                $codebase,
                $return_type,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate_class_constants,
                $evaluate_conditional_types,
                $final,
                $expand_generic,
                $expand_templates
            );
        }

        return $return_type;
    }

    /**
     * @param  string|Type\Atomic\TNamedObject|Type\Atomic\TTemplateParam|null $static_class_type
     * @return Type\Atomic\TNamedObject|Type\Atomic\TTemplateParam
     */
    private static function expandNamedObject(
        Codebase $codebase,
        Type\Atomic\TNamedObject $return_type,
        ?string $self_class,
        $static_class_type,
        ?string $parent_class,
        bool $final = false,
        bool &$expand_generic = false
    ) {
        if ($expand_generic
            && \get_class($return_type) === TNamedObject::class
            && !$return_type->extra_types
            && $codebase->classOrInterfaceExists($return_type->value)
        ) {
            $value = $codebase->classlikes->getUnAliasedName($return_type->value);
            $container_class_storage = $codebase->classlike_storage_provider->get(
                $value
            );

            if ($container_class_storage->template_types
                && \array_filter(
                    $container_class_storage->template_types,
                    function ($type_map) {
                        return !reset($type_map)->hasMixed();
                    }
                )
            ) {
                $return_type = new Type\Atomic\TGenericObject(
                    $return_type->value,
                    \array_values(
                        \array_map(
                            function ($type_map) {
                                return clone reset($type_map);
                            },
                            $container_class_storage->template_types
                        )
                    )
                );

                // we don't want to expand generic types recursively
                $expand_generic = false;
            }
        }

        $return_type_lc = strtolower($return_type->value);

        if ($static_class_type && ($return_type_lc === 'static' || $return_type_lc === '$this')) {
            if (is_string($static_class_type)) {
                $return_type->value = $static_class_type;
            } else {
                if ($return_type instanceof Type\Atomic\TGenericObject
                    && $static_class_type instanceof Type\Atomic\TGenericObject
                ) {
                    $return_type->value = $static_class_type->value;
                } else {
                    $return_type = clone $static_class_type;
                }
            }

            if (!$final && $return_type instanceof TNamedObject) {
                $return_type->was_static = true;
            }
        } elseif ($return_type->was_static
            && ($static_class_type instanceof Type\Atomic\TNamedObject
                || $static_class_type instanceof Type\Atomic\TTemplateParam)
        ) {
            $return_type = clone $return_type;
            $cloned_static = clone $static_class_type;
            $extra_static = $cloned_static->extra_types ?: [];
            $cloned_static->extra_types = null;

            if ($cloned_static->getKey(false) !== $return_type->getKey(false)) {
                $return_type->extra_types[$static_class_type->getKey()] = clone $cloned_static;
            }

            foreach ($extra_static as $extra_static_type) {
                if ($extra_static_type->getKey(false) !== $return_type->getKey(false)) {
                    $return_type->extra_types[$extra_static_type->getKey()] = clone $extra_static_type;
                }
            }
        } elseif ($return_type->was_static && is_string($static_class_type) && $final) {
            $return_type->value = $static_class_type;
            $return_type->was_static = false;
        } elseif ($self_class && $return_type_lc === 'self') {
            $return_type->value = $self_class;
        } elseif ($parent_class && $return_type_lc === 'parent') {
            $return_type->value = $parent_class;
        } else {
            $return_type->value = $codebase->classlikes->getUnAliasedName($return_type->value);
        }

        return $return_type;
    }

    /**
     * @param  string|Type\Atomic\TNamedObject|Type\Atomic\TTemplateParam|null $static_class_type
     *
     * @return Type\Atomic|non-empty-list<Type\Atomic>
     */
    private static function expandConditional(
        Codebase $codebase,
        Type\Atomic\TConditional $return_type,
        ?string $self_class,
        $static_class_type,
        ?string $parent_class,
        bool $evaluate_class_constants = true,
        bool $evaluate_conditional_types = false,
        bool $final = false,
        bool &$expand_generic = false,
        bool $expand_templates = false
    ) {
        $new_as_type = self::expandUnion(
            $codebase,
            $return_type->as_type,
            $self_class,
            $static_class_type,
            $parent_class,
            $evaluate_class_constants,
            $evaluate_conditional_types,
            $final,
            $expand_generic,
            $expand_templates
        );

        $return_type->as_type = $new_as_type;

        if ($evaluate_conditional_types) {
            $assertion = null;

            if ($return_type->conditional_type->isSingle()) {
                foreach ($return_type->conditional_type->getAtomicTypes() as $condition_atomic_type) {
                    $candidate = self::expandAtomic(
                        $codebase,
                        $condition_atomic_type,
                        $self_class,
                        $static_class_type,
                        $parent_class,
                        $evaluate_class_constants,
                        $evaluate_conditional_types,
                        $final,
                        $expand_generic,
                        $expand_templates
                    );

                    if (!is_array($candidate)) {
                        $assertion = $candidate->getAssertionString();
                    }
                }
            }

            $if_conditional_return_types = [];

            foreach ($return_type->if_type->getAtomicTypes() as $if_atomic_type) {
                $candidate = self::expandAtomic(
                    $codebase,
                    $if_atomic_type,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates
                );

                $candidate_types = is_array($candidate) ? $candidate : [$candidate];

                $if_conditional_return_types = array_merge(
                    $if_conditional_return_types,
                    $candidate_types
                );
            }

            $else_conditional_return_types = [];

            foreach ($return_type->else_type->getAtomicTypes() as $else_atomic_type) {
                $candidate = self::expandAtomic(
                    $codebase,
                    $else_atomic_type,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates
                );

                $candidate_types = is_array($candidate) ? $candidate : [$candidate];

                $else_conditional_return_types = array_merge(
                    $else_conditional_return_types,
                    $candidate_types
                );
            }

            if ($assertion && $return_type->param_name === (string) $return_type->if_type) {
                $if_conditional_return_type = TypeCombiner::combine(
                    $if_conditional_return_types,
                    $codebase
                );

                $if_conditional_return_type = \Psalm\Internal\Type\SimpleAssertionReconciler::reconcile(
                    $assertion,
                    $codebase,
                    $if_conditional_return_type
                );


                if ($if_conditional_return_type) {
                    $if_conditional_return_types = array_values($if_conditional_return_type->getAtomicTypes());
                }
            }

            if ($assertion && $return_type->param_name === (string) $return_type->else_type) {
                $else_conditional_return_type = TypeCombiner::combine(
                    $else_conditional_return_types,
                    $codebase
                );

                $else_conditional_return_type = \Psalm\Internal\Type\SimpleNegatedAssertionReconciler::reconcile(
                    $assertion,
                    $else_conditional_return_type
                );

                if ($else_conditional_return_type) {
                    $else_conditional_return_types = array_values($else_conditional_return_type->getAtomicTypes());
                }
            }

            $all_conditional_return_types = array_merge(
                $if_conditional_return_types,
                $else_conditional_return_types
            );

            foreach ($all_conditional_return_types as $i => $conditional_return_type) {
                if ($conditional_return_type instanceof Type\Atomic\TVoid
                    && count($all_conditional_return_types) > 1
                ) {
                    $all_conditional_return_types[$i] = new Type\Atomic\TNull();
                    $all_conditional_return_types[$i]->from_docblock = true;
                }
            }

            $combined = TypeCombiner::combine(
                array_values($all_conditional_return_types),
                $codebase
            );

            return array_values($combined->getAtomicTypes());
        }

        $return_type->conditional_type = self::expandUnion(
            $codebase,
            $return_type->conditional_type,
            $self_class,
            $static_class_type,
            $parent_class,
            $evaluate_class_constants,
            $evaluate_conditional_types,
            $final,
            $expand_generic,
            $expand_templates
        );

        $return_type->if_type = self::expandUnion(
            $codebase,
            $return_type->if_type,
            $self_class,
            $static_class_type,
            $parent_class,
            $evaluate_class_constants,
            $evaluate_conditional_types,
            $final,
            $expand_generic,
            $expand_templates
        );

        $return_type->else_type = self::expandUnion(
            $codebase,
            $return_type->else_type,
            $self_class,
            $static_class_type,
            $parent_class,
            $evaluate_class_constants,
            $evaluate_conditional_types,
            $final,
            $expand_generic,
            $expand_templates
        );

        return $return_type;
    }
}
