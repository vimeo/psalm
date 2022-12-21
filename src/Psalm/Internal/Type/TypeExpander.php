<?php

namespace Psalm\Internal\Type;

use Psalm\Codebase;
use Psalm\Exception\CircularReferenceException;
use Psalm\Exception\UnresolvableConstantException;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\AtomicPropertyFetchAnalyzer;
use Psalm\Internal\Type\SimpleAssertionReconciler;
use Psalm\Internal\Type\SimpleNegatedAssertionReconciler;
use Psalm\Internal\Type\TypeParser;
use Psalm\Storage\Assertion\IsType;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntMask;
use Psalm\Type\Atomic\TIntMaskOf;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyOf;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TPropertiesOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTypeAlias;
use Psalm\Type\Atomic\TValueOf;
use Psalm\Type\Atomic\TVoid;
use Psalm\Type\Union;
use ReflectionProperty;

use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function get_class;
use function is_string;
use function reset;
use function strpos;
use function strtolower;
use function substr;

/**
 * @internal
 */
class TypeExpander
{
    /**
     * @psalm-suppress InaccessibleProperty We just created the type
     * @param string|TNamedObject|TTemplateParam|null $static_class_type
     */
    public static function expandUnion(
        Codebase $codebase,
        Union $return_type,
        ?string $self_class,
        $static_class_type,
        ?string $parent_class,
        bool $evaluate_class_constants = true,
        bool $evaluate_conditional_types = false,
        bool $final = false,
        bool $expand_generic = false,
        bool $expand_templates = false,
        bool $throw_on_unresolvable_constant = false
    ): Union {
        $new_return_type_parts = [];

        $had_split_values = false;

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
                $expand_templates,
                $throw_on_unresolvable_constant,
            );

            if ($return_type_part instanceof TTypeAlias || count($parts) > 1) {
                $had_split_values = true;
            }

            $new_return_type_parts = [...$new_return_type_parts, ...$parts];
        }

        if ($had_split_values) {
            $fleshed_out_type = TypeCombiner::combine(
                $new_return_type_parts,
                $codebase,
            );
        } else {
            $fleshed_out_type = new Union($new_return_type_parts);
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
        $fleshed_out_type->explicit_never = $return_type->explicit_never;
        $fleshed_out_type->had_template = $return_type->had_template;
        $fleshed_out_type->parent_nodes = $return_type->parent_nodes;

        return $fleshed_out_type;
    }

    /**
     * @param string|TNamedObject|TTemplateParam|null $static_class_type
     * @param-out Atomic $return_type
     * @return non-empty-list<Atomic>
     * @psalm-suppress ConflictingReferenceConstraint, ReferenceConstraintViolation The output type is always Atomic
     * @psalm-suppress ComplexMethod
     */
    public static function expandAtomic(
        Codebase $codebase,
        Atomic &$return_type,
        ?string $self_class,
        $static_class_type,
        ?string $parent_class,
        bool $evaluate_class_constants = true,
        bool $evaluate_conditional_types = false,
        bool $final = false,
        bool $expand_generic = false,
        bool $expand_templates = false,
        bool $throw_on_unresolvable_constant = false
    ): array {
        if ($return_type instanceof TNamedObject
            || $return_type instanceof TTemplateParam
        ) {
            if ($return_type->extra_types) {
                $new_intersection_types = [];

                $extra_types = [];
                foreach ($return_type->extra_types as $extra_type) {
                    self::expandAtomic(
                        $codebase,
                        $extra_type,
                        $self_class,
                        $static_class_type,
                        $parent_class,
                        $evaluate_class_constants,
                        $evaluate_conditional_types,
                        $expand_generic,
                        $expand_templates,
                        $throw_on_unresolvable_constant,
                    );

                    if ($extra_type instanceof TNamedObject && $extra_type->extra_types) {
                        $new_intersection_types = array_merge(
                            $new_intersection_types,
                            $extra_type->extra_types,
                        );
                        $extra_type = $extra_type->setIntersectionTypes([]);
                    }
                    $extra_types[$extra_type->getKey()] = $extra_type;
                }

                /** @psalm-suppress ArgumentTypeCoercion */
                $return_type = $return_type->setIntersectionTypes(array_merge($extra_types, $new_intersection_types));
            }

            if ($return_type instanceof TNamedObject) {
                $return_type = self::expandNamedObject(
                    $codebase,
                    $return_type,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $final,
                    $expand_generic,
                );
            }
        }

        if ($return_type instanceof TClassString
            && $return_type->as_type
        ) {
            $new_as_type = $return_type->as_type;

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
                $expand_templates,
                $throw_on_unresolvable_constant,
            );

            if ($new_as_type instanceof TNamedObject && $new_as_type !== $return_type->as_type) {
                $return_type = $return_type->setAs(
                    $new_as_type->value,
                    $new_as_type,
                );
            }
        } elseif ($return_type instanceof TTemplateParam) {
            $new_as_type = self::expandUnion(
                $codebase,
                $return_type->as,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate_class_constants,
                $evaluate_conditional_types,
                $final,
                $expand_generic,
                $expand_templates,
                $throw_on_unresolvable_constant,
            );

            if ($expand_templates) {
                return array_values($new_as_type->getAtomicTypes());
            }

            $return_type = $return_type->replaceAs($new_as_type);
        }

        if ($return_type instanceof TClassConstant) {
            if ($self_class) {
                $return_type = $return_type->replaceClassLike(
                    'self',
                    $self_class,
                );
            }
            if (is_string($static_class_type) || $self_class) {
                $return_type = $return_type->replaceClassLike(
                    'static',
                    is_string($static_class_type) ? $static_class_type : $self_class,
                );
            }

            if ($evaluate_class_constants && $codebase->classOrInterfaceOrEnumExists($return_type->fq_classlike_name)) {
                if (strtolower($return_type->const_name) === 'class') {
                    return [new TLiteralClassString($return_type->fq_classlike_name)];
                }

                $class_storage = $codebase->classlike_storage_provider->get($return_type->fq_classlike_name);

                if (strpos($return_type->const_name, '*') !== false) {
                    $matching_constants = [
                        ...array_keys($class_storage->constants),
                        ...array_keys($class_storage->enum_cases),
                    ];

                    $const_name_part = substr($return_type->const_name, 0, -1);

                    if ($const_name_part) {
                        $matching_constants = array_filter(
                            $matching_constants,
                            static fn($constant_name): bool => $constant_name !== $const_name_part
                                && strpos($constant_name, $const_name_part) === 0
                        );
                    }
                } else {
                    $matching_constants = [$return_type->const_name];
                }

                $matching_constant_types = [];

                foreach ($matching_constants as $matching_constant) {
                    try {
                        $class_constant = $codebase->classlikes->getClassConstantType(
                            $return_type->fq_classlike_name,
                            $matching_constant,
                            ReflectionProperty::IS_PRIVATE,
                        );
                    } catch (CircularReferenceException $e) {
                        $class_constant = null;
                    }

                    if ($class_constant) {
                        if ($class_constant->isSingle()) {
                            $matching_constant_types = array_merge(
                                array_values($class_constant->getAtomicTypes()),
                                $matching_constant_types,
                            );
                        }
                    }
                }

                if ($matching_constant_types) {
                    return $matching_constant_types;
                }
            }

            return [$return_type];
        }

        if ($return_type instanceof TPropertiesOf) {
            return self::expandPropertiesOf(
                $codebase,
                $return_type,
                $self_class,
                $static_class_type,
            );
        }

        if ($return_type instanceof TTypeAlias) {
            $declaring_fq_classlike_name = $return_type->declaring_fq_classlike_name;

            if ($declaring_fq_classlike_name === 'self' && $self_class) {
                $declaring_fq_classlike_name = $self_class;
            }

            if (!($evaluate_class_constants && $codebase->classOrInterfaceExists($declaring_fq_classlike_name))) {
                return [$return_type];
            }

            $class_storage = $codebase->classlike_storage_provider->get($declaring_fq_classlike_name);

            $type_alias_name = $return_type->alias_name;

            if (!isset($class_storage->type_aliases[$type_alias_name])) {
                return [$return_type];
            }

            $resolved_type_alias = $class_storage->type_aliases[$type_alias_name];
            $replacement_atomic_types = $resolved_type_alias->replacement_atomic_types;

            if (!$replacement_atomic_types) {
                return [$return_type];
            }

            $recursively_fleshed_out_types = [];
            foreach ($replacement_atomic_types as $replacement_atomic_type) {
                $more_recursively_fleshed_out_types = self::expandAtomic(
                    $codebase,
                    $replacement_atomic_type,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates,
                    $throw_on_unresolvable_constant,
                );

                $recursively_fleshed_out_types = [
                    ...$more_recursively_fleshed_out_types,
                    ...$recursively_fleshed_out_types,
                ];
            }

            foreach ($return_type->extra_types ?? [] as $alias) {
                $more_recursively_fleshed_out_types = self::expandAtomic(
                    $codebase,
                    $alias,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates,
                    $throw_on_unresolvable_constant,
                );

                $recursively_fleshed_out_types = [
                    ...$more_recursively_fleshed_out_types,
                    ...$recursively_fleshed_out_types,
                ];
            }

            return $recursively_fleshed_out_types;
        }

        if ($return_type instanceof TKeyOf
            || $return_type instanceof TValueOf
        ) {
            return self::expandKeyOfValueOf(
                $codebase,
                $return_type,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate_class_constants,
                $evaluate_conditional_types,
                $final,
                $expand_generic,
                $expand_templates,
                $throw_on_unresolvable_constant,
            );
        }

        if ($return_type instanceof TIntMask) {
            if (!$evaluate_class_constants) {
                return [new TInt()];
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
                    $expand_templates,
                    $throw_on_unresolvable_constant,
                );

                $new_value_type = reset($new_value_type);

                if (!$new_value_type instanceof TLiteralInt) {
                    return [new TInt()];
                }

                $potential_ints[] = $new_value_type->value;
            }

            return TypeParser::getComputedIntsFromMask($potential_ints);
        }

        if ($return_type instanceof TIntMaskOf) {
            if (!$evaluate_class_constants) {
                return [new TInt()];
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
                $expand_templates,
                $throw_on_unresolvable_constant,
            );

            $potential_ints = [];

            foreach ($new_value_types as $new_value_type) {
                if (!$new_value_type instanceof TLiteralInt) {
                    return [new TInt()];
                }

                $potential_ints[] = $new_value_type->value;
            }

            return TypeParser::getComputedIntsFromMask($potential_ints);
        }

        if ($return_type instanceof TConditional) {
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
                $expand_templates,
                $throw_on_unresolvable_constant,
            );
        }
        if ($return_type instanceof TList) {
            $return_type = $return_type->getKeyedArray();
        }

        if ($return_type instanceof TArray
            || $return_type instanceof TGenericObject
            || $return_type instanceof TIterable
        ) {
            $type_params = $return_type->type_params;
            foreach ($type_params as &$type_param) {
                $type_param = self::expandUnion(
                    $codebase,
                    $type_param,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates,
                    $throw_on_unresolvable_constant,
                );
            }
            unset($type_param);
            /** @psalm-suppress InvalidArgument Psalm bug */
            $return_type = $return_type->setTypeParams($type_params);
        } elseif ($return_type instanceof TKeyedArray) {
            $properties = $return_type->properties;
            $changed = false;
            foreach ($properties as $k => $property_type) {
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
                    $expand_templates,
                    $throw_on_unresolvable_constant,
                );
                if ($property_type !== $properties[$k]) {
                    $changed = true;
                    $properties[$k] = $property_type;
                }
            }
            unset($property_type);
            $fallback_params = $return_type->fallback_params;
            if ($fallback_params) {
                foreach ($fallback_params as $k => $property_type) {
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
                        $expand_templates,
                        $throw_on_unresolvable_constant,
                    );
                    if ($property_type !== $fallback_params[$k]) {
                        $changed = true;
                        $fallback_params[$k] = $property_type;
                    }
                }
                unset($property_type);
            }
            if ($changed) {
                $return_type = new TKeyedArray(
                    $properties,
                    $return_type->class_strings,
                    $fallback_params,
                    $return_type->is_list,
                    $return_type->from_docblock,
                );
            }
        }

        if ($return_type instanceof TObjectWithProperties) {
            $properties = $return_type->properties;
            foreach ($properties as &$property_type) {
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
                    $expand_templates,
                    $throw_on_unresolvable_constant,
                );
            }
            unset($property_type);
            $return_type = $return_type->setProperties($properties);
        }

        if ($return_type instanceof TCallable
            || $return_type instanceof TClosure
        ) {
            $params = $return_type->params;
            if ($params) {
                foreach ($params as &$param) {
                    if ($param->type) {
                        $param = $param->setType(self::expandUnion(
                            $codebase,
                            $param->type,
                            $self_class,
                            $static_class_type,
                            $parent_class,
                            $evaluate_class_constants,
                            $evaluate_conditional_types,
                            $final,
                            $expand_generic,
                            $expand_templates,
                            $throw_on_unresolvable_constant,
                        ));
                    }
                }
                unset($param);
            }
            $sub_return_type = $return_type->return_type;
            if ($sub_return_type) {
                $sub_return_type = self::expandUnion(
                    $codebase,
                    $sub_return_type,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates,
                    $throw_on_unresolvable_constant,
                );
            }

            $return_type = $return_type->replace(
                $params,
                $sub_return_type,
            );
        }

        return [$return_type];
    }

    /**
     * @param string|TNamedObject|TTemplateParam|null $static_class_type
     * @param-out TNamedObject|TTemplateParam $return_type
     * @return TNamedObject|TTemplateParam
     */
    private static function expandNamedObject(
        Codebase $codebase,
        TNamedObject &$return_type,
        ?string $self_class,
        $static_class_type,
        ?string $parent_class,
        bool $final = false,
        bool &$expand_generic = false
    ) {
        if ($expand_generic
            && get_class($return_type) === TNamedObject::class
            && !$return_type->extra_types
            && $codebase->classOrInterfaceExists($return_type->value)
        ) {
            $value = $codebase->classlikes->getUnAliasedName($return_type->value);
            $container_class_storage = $codebase->classlike_storage_provider->get(
                $value,
            );

            if ($container_class_storage->template_types
                && array_filter(
                    $container_class_storage->template_types,
                    static fn($type_map): bool => !reset($type_map)->hasMixed()
                )
            ) {
                $return_type = new TGenericObject(
                    $return_type->value,
                    array_values(
                        array_map(
                            static fn($type_map) => reset($type_map),
                            $container_class_storage->template_types,
                        ),
                    ),
                );

                // we don't want to expand generic types recursively
                $expand_generic = false;
            }
        }

        $return_type_lc = strtolower($return_type->value);

        if ($static_class_type && ($return_type_lc === 'static' || $return_type_lc === '$this')) {
            $is_static = $return_type->is_static;
            $is_static_resolved = null;
            if (!$final) {
                $is_static = true;
                $is_static_resolved = true;
            }
            if (is_string($static_class_type)) {
                $return_type = $return_type->setValueIsStatic(
                    $static_class_type,
                    $is_static,
                    $is_static_resolved,
                );
            } else {
                if ($return_type instanceof TGenericObject
                    && $static_class_type instanceof TGenericObject
                ) {
                    $return_type = $return_type->setValueIsStatic(
                        $static_class_type->value,
                        $is_static,
                        $is_static_resolved,
                    );
                } elseif ($static_class_type instanceof TNamedObject) {
                    $return_type = $static_class_type->setIsStatic(
                        $is_static,
                        $is_static_resolved,
                    );
                } else {
                    $return_type = $static_class_type;
                }
            }
        } elseif ($return_type->is_static && !$return_type->is_static_resolved
            && ($static_class_type instanceof TNamedObject
                || $static_class_type instanceof TTemplateParam)
        ) {
            $return_type_types = $return_type->getIntersectionTypes();
            $cloned_static = $static_class_type->setIntersectionTypes([]);
            $extra_static = $static_class_type->extra_types;

            if ($cloned_static->getKey(false) !== $return_type->getKey(false)) {
                $return_type_types[$cloned_static->getKey()] = $cloned_static;
            }

            foreach ($extra_static as $extra_static_type) {
                if ($extra_static_type->getKey(false) !== $return_type->getKey(false)) {
                    $return_type_types[$extra_static_type->getKey()] = $extra_static_type;
                }
            }
            $return_type = $return_type->setIntersectionTypes($return_type_types)
                ->setIsStatic(true, true);
        } elseif ($return_type->is_static && is_string($static_class_type) && $final) {
            $return_type = $return_type->setValueIsStatic(
                $static_class_type,
                false,
            );
        } elseif ($self_class && $return_type_lc === 'self') {
            $return_type = $return_type->setValue($self_class);
        } elseif ($parent_class && $return_type_lc === 'parent') {
            $return_type = $return_type->setValue($parent_class);
        } else {
            $new_value = $codebase->classlikes->getUnAliasedName($return_type->value);
            $return_type = $return_type->setValue($new_value);
        }

        return $return_type;
    }

    /**
     * @param string|TNamedObject|TTemplateParam|null $static_class_type
     * @return non-empty-list<Atomic>
     */
    private static function expandConditional(
        Codebase $codebase,
        TConditional &$return_type,
        ?string $self_class,
        $static_class_type,
        ?string $parent_class,
        bool $evaluate_class_constants = true,
        bool $evaluate_conditional_types = false,
        bool $final = false,
        bool $expand_generic = false,
        bool $expand_templates = false,
        bool $throw_on_unresolvable_constant = false
    ): array {
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
            $expand_templates,
            $throw_on_unresolvable_constant,
        );

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
                        $expand_templates,
                        $throw_on_unresolvable_constant,
                    );

                    if (count($candidate) === 1) {
                        $assertion = new IsType($candidate[0]);
                    }
                }
            }

            $if_conditional_return_types = [];

            foreach ($return_type->if_type->getAtomicTypes() as $if_atomic_type) {
                $candidate_types = self::expandAtomic(
                    $codebase,
                    $if_atomic_type,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates,
                    $throw_on_unresolvable_constant,
                );

                $if_conditional_return_types = [...$if_conditional_return_types, ...$candidate_types];
            }

            $else_conditional_return_types = [];

            foreach ($return_type->else_type->getAtomicTypes() as $else_atomic_type) {
                $candidate_types = self::expandAtomic(
                    $codebase,
                    $else_atomic_type,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates,
                    $throw_on_unresolvable_constant,
                );

                $else_conditional_return_types = [...$else_conditional_return_types, ...$candidate_types];
            }

            if ($assertion && $return_type->param_name === (string) $return_type->if_type) {
                $if_conditional_return_type = TypeCombiner::combine(
                    $if_conditional_return_types,
                    $codebase,
                );

                $if_conditional_return_type = SimpleAssertionReconciler::reconcile(
                    $assertion,
                    $codebase,
                    $if_conditional_return_type,
                );


                if ($if_conditional_return_type) {
                    $if_conditional_return_types = array_values($if_conditional_return_type->getAtomicTypes());
                }
            }

            if ($assertion && $return_type->param_name === (string) $return_type->else_type) {
                $else_conditional_return_type = TypeCombiner::combine(
                    $else_conditional_return_types,
                    $codebase,
                );

                $else_conditional_return_type = SimpleNegatedAssertionReconciler::reconcile(
                    $codebase,
                    $assertion,
                    $else_conditional_return_type,
                );

                if ($else_conditional_return_type) {
                    $else_conditional_return_types = array_values($else_conditional_return_type->getAtomicTypes());
                }
            }

            $all_conditional_return_types = [...$if_conditional_return_types, ...$else_conditional_return_types];

            $number_of_types = count($all_conditional_return_types);
            // we filter TNever that have no bearing on the return type
            if ($number_of_types > 1) {
                $all_conditional_return_types = array_filter(
                    $all_conditional_return_types,
                    static fn(Atomic $atomic_type): bool => !$atomic_type instanceof TNever,
                );
            }

            // if we still have more than one type, we remove TVoid and replace it by TNull
            $number_of_types = count($all_conditional_return_types);
            if ($number_of_types > 1) {
                $all_conditional_return_types = array_filter(
                    $all_conditional_return_types,
                    static fn(Atomic $atomic_type): bool => !$atomic_type instanceof TVoid,
                );

                if (count($all_conditional_return_types) !== $number_of_types) {
                    $all_conditional_return_types[] = new TNull(true);
                }
            }

            if ($all_conditional_return_types) {
                $combined = TypeCombiner::combine(
                    array_values($all_conditional_return_types),
                    $codebase,
                );

                $return_type = $return_type->setTypes($new_as_type);

                return array_values($combined->getAtomicTypes());
            }
        }

        $return_type = $return_type->setTypes(
            $new_as_type,
            self::expandUnion(
                $codebase,
                $return_type->conditional_type,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate_class_constants,
                $evaluate_conditional_types,
                $final,
                $expand_generic,
                $expand_templates,
                $throw_on_unresolvable_constant,
            ),
            self::expandUnion(
                $codebase,
                $return_type->if_type,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate_class_constants,
                $evaluate_conditional_types,
                $final,
                $expand_generic,
                $expand_templates,
                $throw_on_unresolvable_constant,
            ),
            self::expandUnion(
                $codebase,
                $return_type->else_type,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate_class_constants,
                $evaluate_conditional_types,
                $final,
                $expand_generic,
                $expand_templates,
                $throw_on_unresolvable_constant,
            ),
        );
        return [$return_type];
    }

    /**
     * @param string|TNamedObject|TTemplateParam|null $static_class_type
     * @return non-empty-list<Atomic>
     */
    private static function expandPropertiesOf(
        Codebase $codebase,
        TPropertiesOf &$return_type,
        ?string $self_class,
        $static_class_type
    ): array {
        if ($self_class) {
            $return_type = $return_type->replaceClassLike(
                'self',
                $self_class,
            );
            $return_type = $return_type->replaceClassLike(
                'static',
                is_string($static_class_type) ? $static_class_type : $self_class,
            );
        }

        $class_storage = null;
        if ($codebase->classExists($return_type->classlike_type->value)) {
            $class_storage = $codebase->classlike_storage_provider->get($return_type->classlike_type->value);
        } else {
            foreach ($return_type->classlike_type->extra_types as $type) {
                if ($type instanceof TNamedObject && $codebase->classExists($type->value)) {
                    $class_storage = $codebase->classlike_storage_provider->get($type->value);
                    break;
                }
            }
        }

        if (!$class_storage) {
            return [$return_type];
        }

        $all_sealed = true;
        $properties = [];
        foreach ([$class_storage->name, ...array_values($class_storage->parent_classes)] as $class) {
            if (!$codebase->classExists($class)) {
                continue;
            }
            $storage = $codebase->classlike_storage_provider->get($class);
            if (!$storage->final) {
                $all_sealed = false;
            }
            foreach ($storage->properties as $key => $property) {
                if (isset($properties[$key])) {
                    continue;
                }
                if ($return_type->visibility_filter !== null
                    && $property->visibility !== $return_type->visibility_filter
                ) {
                    continue;
                }
                if ($property->is_static || !$property->type) {
                    continue;
                }
                $type = $return_type->classlike_type instanceof TGenericObject
                    ? AtomicPropertyFetchAnalyzer::localizePropertyType(
                        $codebase,
                        $property->type,
                        $return_type->classlike_type,
                        $storage,
                        $storage,
                    )
                    : $property->type
                ;
                $properties[$key] = $type;
            }
        }

        if ($properties === []) {
            return [$return_type];
        }
        return [new TKeyedArray(
            $properties,
            null,
            $all_sealed ? null : [Type::getString(), Type::getMixed()],
        )];
    }

    /**
     * @param TKeyOf|TValueOf $return_type
     * @param string|TNamedObject|TTemplateParam|null $static_class_type
     * @return non-empty-list<Atomic>
     */
    private static function expandKeyOfValueOf(
        Codebase $codebase,
        Atomic &$return_type,
        ?string $self_class,
        $static_class_type,
        ?string $parent_class,
        bool $evaluate_class_constants = true,
        bool $evaluate_conditional_types = false,
        bool $final = false,
        bool $expand_generic = false,
        bool $expand_templates = false,
        bool $throw_on_unresolvable_constant = false
    ): array {
        // Expand class constants to their atomics
        $type_atomics = [];
        foreach ($return_type->type->getAtomicTypes() as $type_param) {
            if (!$evaluate_class_constants || !$type_param instanceof TClassConstant) {
                $type_param_expanded = self::expandAtomic(
                    $codebase,
                    $type_param,
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates,
                    $throw_on_unresolvable_constant,
                );
                $type_atomics = [...$type_atomics, ...$type_param_expanded];
                continue;
            }

            if ($self_class) {
                $type_param = $type_param->replaceClassLike('self', $self_class);
            }

            if ($throw_on_unresolvable_constant
                && !$codebase->classOrInterfaceExists($type_param->fq_classlike_name)
            ) {
                throw new UnresolvableConstantException($type_param->fq_classlike_name, $type_param->const_name);
            }

            try {
                $constant_type = $codebase->classlikes->getClassConstantType(
                    $type_param->fq_classlike_name,
                    $type_param->const_name,
                    ReflectionProperty::IS_PRIVATE,
                );
            } catch (CircularReferenceException $e) {
                return [$return_type];
            }

            if (!$constant_type
                || (
                    $return_type instanceof TKeyOf
                    && !TKeyOf::isViableTemplateType($constant_type)
                )
                || (
                    $return_type instanceof TValueOf
                    && !TValueOf::isViableTemplateType($constant_type)
                )
            ) {
                if ($throw_on_unresolvable_constant) {
                    throw new UnresolvableConstantException($type_param->fq_classlike_name, $type_param->const_name);
                } else {
                    return [$return_type];
                }
            }

            $type_atomics = array_merge(
                $type_atomics,
                array_values($constant_type->getAtomicTypes()),
            );
        }
        if ($type_atomics === []) {
            return [$return_type];
        }

        if ($return_type instanceof TKeyOf) {
            $new_return_types = TKeyOf::getArrayKeyType(new Union($type_atomics));
        } else {
            $new_return_types = TValueOf::getValueType(new Union($type_atomics), $codebase);
        }
        if ($new_return_types === null) {
            return [$return_type];
        }
        return array_values($new_return_types->getAtomicTypes());
    }
}
