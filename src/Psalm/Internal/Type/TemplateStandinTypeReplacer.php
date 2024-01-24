<?php

namespace Psalm\Internal\Type;

use InvalidArgumentException;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\Methods;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Internal\Type\Comparator\KeyedArrayComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TDependentGetClass;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TPropertiesOf;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTemplatePropertiesOf;
use Psalm\Type\Atomic\TTemplateValueOf;
use Psalm\Type\Union;

use function array_fill;
use function array_filter;
use function array_keys;
use function array_merge;
use function array_search;
use function array_slice;
use function array_values;
use function count;
use function in_array;
use function reset;
use function strpos;
use function strtolower;
use function substr;
use function usort;

/**
 * @internal
 */
final class TemplateStandinTypeReplacer
{
    /**
     * This method fills in the values in $template_result based on how the various atomic types
     * of $union_type match up to the types inside $input_type.
     */
    public static function fillTemplateResult(
        Union $union_type,
        TemplateResult $template_result,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer,
        ?Union $input_type,
        ?int $input_arg_offset = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_lower_bound = false,
        ?string $bound_equality_classlike = null,
        int $depth = 1
    ): void {
        self::replace(
            $union_type,
            $template_result,
            $codebase,
            $statements_analyzer,
            $input_type,
            $input_arg_offset,
            $calling_class,
            $calling_function,
            $replace,
            $add_lower_bound,
            $bound_equality_classlike,
            $depth,
        );
    }
    /**
     * This replaces template types in unions with standins (normally the template as type)
     *
     * $input_type here is normally the argument passed to a templated function or method.
     *
     * This method fills in the values in $template_result based on how the various atomic types
     * of $union_type match up to the types inside $input_type
     */
    public static function replace(
        Union $union_type,
        TemplateResult $template_result,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer,
        ?Union $input_type,
        ?int $input_arg_offset = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_lower_bound = false,
        ?string $bound_equality_classlike = null,
        int $depth = 1
    ): Union {
        $atomic_types = [];

        $original_atomic_types = $union_type->getAtomicTypes();

        // here we want to subtract atomic types from the input type
        // when they're also in the union type, so those shared atomic
        // types will never be inferred as part of the generic type
        if ($input_type && !$input_type->isSingle()) {
            $new_input_type = $input_type->getBuilder();

            foreach ($original_atomic_types as $key => $_) {
                if ($new_input_type->hasType($key)) {
                    $new_input_type->removeType($key);
                }
            }

            if (!$new_input_type->isUnionEmpty()) {
                $input_type = $new_input_type->freeze();
            } else {
                return $union_type;
            }
        }

        $had_template = false;

        foreach ($original_atomic_types as $key => $atomic_type) {
            $atomic_types = [...$atomic_types, ...self::handleAtomicStandin(
                $atomic_type,
                $key,
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_lower_bound,
                $bound_equality_classlike,
                $depth,
                count($original_atomic_types) === 1,
                $had_template,
            )];
        }

        if ($replace) {
            if (array_values($original_atomic_types) === $atomic_types) {
                return $union_type;
            }

            if (!$atomic_types) {
                return $union_type;
            }

            if (count($atomic_types) > 1) {
                return TypeCombiner::combine(
                    $atomic_types,
                    $codebase,
                )->setProperties([
                    'ignore_nullable_issues' => $union_type->ignore_nullable_issues,
                    'ignore_falsable_issues' => $union_type->ignore_falsable_issues,
                    'possibly_undefined' => $union_type->possibly_undefined,
                    'had_template' => $had_template,
                ]);
            }

            return new Union($atomic_types, [
                'ignore_nullable_issues' => $union_type->ignore_nullable_issues,
                'ignore_falsable_issues' => $union_type->ignore_falsable_issues,
                'possibly_undefined' => $union_type->possibly_undefined,
                'had_template' => $had_template,
            ]);
        }

        return $union_type;
    }

    /**
     * @return list<Atomic>
     */
    private static function handleAtomicStandin(
        Atomic $atomic_type,
        string $key,
        TemplateResult $template_result,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer,
        ?Union $input_type,
        ?int $input_arg_offset,
        ?string $calling_class,
        ?string $calling_function,
        bool $replace,
        bool $add_lower_bound,
        ?string $bound_equality_classlike,
        int $depth,
        bool $was_single,
        bool &$had_template
    ): array {
        if ($bracket_pos = strpos($key, '<')) {
            $key = substr($key, 0, $bracket_pos);
        }

        if ($atomic_type instanceof TTemplateParam
            && isset($template_result->template_types[$atomic_type->param_name][$atomic_type->defining_class])
        ) {
            return self::handleTemplateParamStandin(
                $atomic_type,
                $key,
                $input_type,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $template_result,
                $codebase,
                $statements_analyzer,
                $replace,
                $add_lower_bound,
                $bound_equality_classlike,
                $depth,
                $had_template,
            );
        }

        if ($atomic_type instanceof TTemplateParam
            && isset($template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class])
        ) {
            $most_specific_type = self::getMostSpecificTypeFromBounds(
                $template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class],
                $codebase,
            );

            return array_values($most_specific_type->getAtomicTypes());
        }

        if ($atomic_type instanceof TTemplateParamClass
            && isset($template_result->template_types[$atomic_type->param_name][$atomic_type->defining_class])
        ) {
            if ($replace) {
                return self::handleTemplateParamClassStandin(
                    $atomic_type,
                    $input_type,
                    $input_arg_offset,
                    $calling_class,
                    $calling_function,
                    $template_result,
                    $codebase,
                    $statements_analyzer,
                    true,
                    $add_lower_bound,
                    $bound_equality_classlike,
                    $depth,
                    $was_single,
                );
            }
        }

        if ($atomic_type instanceof TTemplateIndexedAccess) {
            if ($replace) {
                $atomic_types = [];

                $include_first = true;

                if (isset($template_result->lower_bounds[$atomic_type->array_param_name][$atomic_type->defining_class])
                    && !empty($template_result->lower_bounds[$atomic_type->offset_param_name])
                ) {
                    $array_template_type
                        = self::getMostSpecificTypeFromBounds(
                            $template_result->lower_bounds[$atomic_type->array_param_name][$atomic_type->defining_class],
                            $codebase,
                        );
                    $offset_template_type
                        = self::getMostSpecificTypeFromBounds(
                            array_values($template_result->lower_bounds[$atomic_type->offset_param_name])[0],
                            $codebase,
                        );

                    if ($array_template_type->isSingle()
                        && $offset_template_type->isSingle()
                        && !$array_template_type->isMixed()
                        && !$offset_template_type->isMixed()
                    ) {
                        $array_template_type = $array_template_type->getSingleAtomic();
                        $offset_template_type = $offset_template_type->getSingleAtomic();

                        if ($array_template_type instanceof TList) {
                            $array_template_type = $array_template_type->getKeyedArray();
                        }
                        if ($array_template_type instanceof TKeyedArray
                            && ($offset_template_type instanceof TLiteralString
                                || $offset_template_type instanceof TLiteralInt)
                            && isset($array_template_type->properties[$offset_template_type->value])
                        ) {
                            $include_first = false;

                            $replacement_type
                                = $array_template_type->properties[$offset_template_type->value];

                            foreach ($replacement_type->getAtomicTypes() as $replacement_atomic_type) {
                                $atomic_types[] = $replacement_atomic_type;
                            }
                        }
                    }
                }

                if ($include_first) {
                    $atomic_types[] = $atomic_type;
                }

                return $atomic_types;
            }

            return [$atomic_type];
        }

        if ($atomic_type instanceof TTemplateKeyOf
            || $atomic_type instanceof TTemplateValueOf) {
            if (!$replace) {
                return [$atomic_type];
            }

            $atomic_types = [];

            $include_first = true;
            $template_type = null;

            if (isset($template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class])) {
                $template_type = self::getMostSpecificTypeFromBounds(
                    $template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class],
                    $codebase,
                );
            } elseif (isset($template_result->template_types[$atomic_type->param_name][$atomic_type->defining_class])) {
                $template_type = $template_result->template_types[$atomic_type->param_name][$atomic_type->defining_class];
            }

            if ($template_type) {
                foreach ($template_type->getAtomicTypes() as $template_atomic) {
                    if ($template_atomic instanceof TList) {
                        $template_atomic = $template_atomic->getKeyedArray();
                    }
                    if (!$template_atomic instanceof TKeyedArray
                        && !$template_atomic instanceof TArray
                    ) {
                        return [$atomic_type];
                    }

                    if ($atomic_type instanceof TTemplateKeyOf) {
                        if ($template_atomic instanceof TKeyedArray) {
                            $template_atomic = $template_atomic->getGenericKeyType();
                        } else {
                            $template_atomic = $template_atomic->type_params[0];
                        }
                    } else {
                        if ($template_atomic instanceof TKeyedArray) {
                            $template_atomic = $template_atomic->getGenericValueType();
                        } else {
                            $template_atomic = $template_atomic->type_params[1];
                        }
                    }

                    $include_first = false;

                    foreach ($template_atomic->getAtomicTypes() as $key_atomic_type) {
                        $atomic_types[] = $key_atomic_type;
                    }
                }
            }

            if ($include_first) {
                $atomic_types[] = $atomic_type;
            }

            return $atomic_types;
        }

        if ($atomic_type instanceof TTemplatePropertiesOf) {
            if (!$replace
                || !isset($template_result->template_types[$atomic_type->param_name][$atomic_type->defining_class])
            ) {
                return [$atomic_type];
            }

            $template_type = $template_result->template_types[$atomic_type->param_name][$atomic_type->defining_class];

            $classlike_type = $template_type->getSingleAtomic();
            if (!$classlike_type instanceof TNamedObject) {
                return [$atomic_type];
            }

            /** @psalm-suppress ReferenceConstraintViolation Psalm bug, $atomic_type is not a reference */
            $atomic_type = new TPropertiesOf(
                $classlike_type,
                $atomic_type->visibility_filter,
            );
            return [$atomic_type];
        }

        $matching_atomic_types = [];

        if ($input_type && !$input_type->hasMixed()) {
            $matching_atomic_types = self::findMatchingAtomicTypesForTemplate(
                $atomic_type,
                $key,
                $codebase,
                $statements_analyzer,
                $input_type,
            );
        }

        if (!$matching_atomic_types) {
            /** @psalm-suppress ReferenceConstraintViolation Psalm bug, $atomic_type is not a reference */
            $atomic_type = $atomic_type->replaceTemplateTypesWithStandins(
                $template_result,
                $codebase,
                $statements_analyzer,
                null,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_lower_bound,
                $depth + 1,
            );

            return [$atomic_type];
        }

        $atomic_types = [];

        foreach ($matching_atomic_types as $matching_atomic_type) {
            $atomic_types[] = $atomic_type->replaceTemplateTypesWithStandins(
                $template_result,
                $codebase,
                $statements_analyzer,
                $matching_atomic_type,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_lower_bound,
                $depth + 1,
            );
        }

        return $atomic_types;
    }

    /**
     * This method attempts to find bits of the input type (normally the argument type of a method call)
     * that match the base type (normally the param type of the method). These matches are used to infer
     * more template types
     *
     * Example: when passing `array<string|int>` to a function that expects `array<T>`, a rule in this method
     * identifies the matching atomic types for `T` as `string|int`
     *
     * @return list<Atomic>
     */
    private static function findMatchingAtomicTypesForTemplate(
        Atomic $base_type,
        string $key,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer,
        Union $input_type
    ): array {
        $matching_atomic_types = [];

        foreach ($input_type->getAtomicTypes() as $input_key => $atomic_input_type) {
            if ($atomic_input_type instanceof TList) {
                $atomic_input_type = $atomic_input_type->getKeyedArray();
            }

            if ($bracket_pos = strpos($input_key, '<')) {
                $input_key = substr($input_key, 0, $bracket_pos);
            }

            if ($input_key === $key) {
                $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                continue;
            }

            if ($atomic_input_type instanceof TClosure && $base_type instanceof TClosure) {
                $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                continue;
            }

            if ($atomic_input_type instanceof TCallable
                && $base_type instanceof TCallable
            ) {
                $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                continue;
            }

            if ($atomic_input_type instanceof TClosure && $base_type instanceof TCallable) {
                $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                continue;
            }

            if (($atomic_input_type instanceof TArray
                    || $atomic_input_type instanceof TKeyedArray)
                && $key === 'iterable'
            ) {
                $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                continue;
            }

            if (strpos($input_key, $key . '&') === 0) {
                $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                continue;
            }

            if ($atomic_input_type instanceof TLiteralClassString
                && $base_type instanceof TClassString
                && $base_type->as_type
            ) {
                try {
                    $classlike_storage =
                        $codebase->classlike_storage_provider->get($atomic_input_type->value);

                    if (!empty($classlike_storage->template_extended_params[$base_type->as_type->value])) {
                        $atomic_input_type = new TClassString(
                            $base_type->as_type->value,
                            new TGenericObject(
                                $base_type->as_type->value,
                                array_values($classlike_storage->template_extended_params[$base_type->as_type->value]),
                            ),
                        );

                        $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                        continue;
                    }
                } catch (InvalidArgumentException $e) {
                    // do nothing
                }
            }

            if ($base_type instanceof TCallable) {
                $matching_atomic_type = CallableTypeComparator::getCallableFromAtomic(
                    $codebase,
                    $atomic_input_type,
                    null,
                    $statements_analyzer,
                );

                if ($matching_atomic_type) {
                    $matching_atomic_types[$matching_atomic_type->getId()] = $matching_atomic_type;
                    continue;
                }
            }

            if ($atomic_input_type instanceof TNamedObject
                && ($base_type instanceof TNamedObject
                    || $base_type instanceof TIterable)
            ) {
                if ($base_type instanceof TIterable) {
                    if ($atomic_input_type->value === 'Traversable') {
                        $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                        continue;
                    }

                    $base_type = new TGenericObject(
                        'Traversable',
                        $base_type->type_params,
                    );
                }

                try {
                    $classlike_storage =
                        $codebase->classlike_storage_provider->get($atomic_input_type->value);

                    if ($atomic_input_type instanceof TGenericObject
                        && isset($classlike_storage->template_extended_params[$base_type->value])
                    ) {
                        $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                        continue;
                    }

                    if (!empty($classlike_storage->template_extended_params[$base_type->value])) {
                        $atomic_input_type = new TGenericObject(
                            $base_type->value,
                            array_values($classlike_storage->template_extended_params[$base_type->value]),
                        );

                        $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                        continue;
                    }

                    if (in_array('Traversable', $classlike_storage->class_implements)
                        && $base_type->value === 'Iterator'
                    ) {
                        $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                        continue;
                    }
                } catch (InvalidArgumentException $e) {
                    // do nothing
                }
            }

            if ($atomic_input_type instanceof TNamedObject
                && $base_type instanceof TObjectWithProperties
            ) {
                $object_with_keys = KeyedArrayComparator::coerceToObjectWithProperties(
                    $codebase,
                    $atomic_input_type,
                    $base_type,
                );

                if ($object_with_keys) {
                    $matching_atomic_types[$object_with_keys->getId()] = $object_with_keys;
                }

                continue;
            }

            if ($atomic_input_type instanceof TTemplateParam) {
                $matching_atomic_types = array_merge(
                    $matching_atomic_types,
                    self::findMatchingAtomicTypesForTemplate(
                        $base_type,
                        $key,
                        $codebase,
                        $statements_analyzer,
                        $atomic_input_type->as,
                    ),
                );
                continue;
            }
        }

        return array_values($matching_atomic_types);
    }

    /**
     * @return list<Atomic>
     */
    private static function handleTemplateParamStandin(
        TTemplateParam &$atomic_type,
        string $key,
        ?Union $input_type,
        ?int $input_arg_offset,
        ?string $calling_class,
        ?string $calling_function,
        TemplateResult $template_result,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer,
        bool $replace,
        bool $add_lower_bound,
        ?string $bound_equality_classlike,
        int $depth,
        bool &$had_template
    ): array {
        if ($atomic_type->defining_class === $calling_class) {
            return [$atomic_type];
        }

        $template_type = $template_result->template_types
            [$atomic_type->param_name]
            [$atomic_type->defining_class];

        if ($template_type->getId() === $key) {
            return array_values($template_type->getAtomicTypes());
        }

        $replacement_type = $template_type;

        $param_name_key = $atomic_type->param_name;

        if (strpos($key, '&')) {
            $param_name_key = $key;
        }

        $extra_types = [];

        if ($atomic_type->extra_types) {
            foreach ($atomic_type->extra_types as $extra_type) {
                $extra_type = self::replace(
                    new Union([$extra_type]),
                    $template_result,
                    $codebase,
                    $statements_analyzer,
                    $input_type,
                    $input_arg_offset,
                    $calling_class,
                    $calling_function,
                    $replace,
                    $add_lower_bound,
                    $bound_equality_classlike,
                    $depth + 1,
                );

                if ($extra_type->isSingle()) {
                    $extra_type = $extra_type->getSingleAtomic();

                    if ($extra_type instanceof TNamedObject
                        || $extra_type instanceof TTemplateParam
                        || $extra_type instanceof TIterable
                        || $extra_type instanceof TObjectWithProperties
                    ) {
                        $extra_types[$extra_type->getKey()] = $extra_type;
                    }
                }
            }
        }

        if ($replace) {
            $atomic_types = [];

            if ($replacement_type->hasMixed()
                && !$atomic_type->as->hasMixed()
            ) {
                foreach ($atomic_type->as->getAtomicTypes() as $as_atomic_type) {
                    $atomic_types[] = $as_atomic_type;
                }
            } else {
                $replacement_type = TypeExpander::expandUnion(
                    $codebase,
                    $replacement_type,
                    $calling_class,
                    $calling_class,
                    null,
                );

                if ($depth < 10) {
                    $replacement_type = self::replace(
                        $replacement_type,
                        $template_result,
                        $codebase,
                        $statements_analyzer,
                        $input_type,
                        $input_arg_offset,
                        $calling_class,
                        $calling_function,
                        true,
                        $add_lower_bound,
                        $bound_equality_classlike,
                        $depth + 1,
                    );
                }

                foreach ($replacement_type->getAtomicTypes() as $replacement_atomic_type) {
                    $replacements_found = false;

                    // @codingStandardsIgnoreStart
                    if ($replacement_atomic_type instanceof TTemplateKeyOf
                        && isset($template_result->template_types[$replacement_atomic_type->param_name][$replacement_atomic_type->defining_class])
                        && count($template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class])
                            === 1
                    ) {
                        $keyed_template = $template_result->template_types[$replacement_atomic_type->param_name][$replacement_atomic_type->defining_class];

                        if ($keyed_template->isSingle()) {
                            $keyed_template = $keyed_template->getSingleAtomic();
                        }
                        if ($keyed_template instanceof \Psalm\Type\Atomic\TList) {
                            $keyed_template = $keyed_template->getKeyedArray();
                        }

                        if ($keyed_template instanceof TKeyedArray
                            || $keyed_template instanceof TArray
                        ) {
                            if ($keyed_template instanceof TKeyedArray) {
                                $key_type = $keyed_template->getGenericKeyType();
                            } else {
                                $key_type = $keyed_template->type_params[0];
                            }

                            $replacements_found = true;

                            foreach ($key_type->getAtomicTypes() as $key_type_atomic) {
                                $atomic_types[] = $key_type_atomic;
                            }

                            $existing_lower_bound = reset($template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class]);

                            $existing_lower_bound->type = $key_type;
                        }
                    }

                    if ($replacement_atomic_type instanceof TTemplateParam
                        && $replacement_atomic_type->defining_class !== $calling_class
                        && $replacement_atomic_type->defining_class !== 'fn-' . $calling_function
                    ) {
                        foreach ($replacement_atomic_type->as->getAtomicTypes() as $nested_type_atomic) {
                            $replacements_found = true;
                            $atomic_types[] = $nested_type_atomic;
                        }
                    }
                    // @codingStandardsIgnoreEnd

                    if (!$replacements_found) {
                        $atomic_types[] = $replacement_atomic_type;
                    }

                    $had_template = true;
                }
            }

            $matching_input_keys = [];

            $as = TypeExpander::expandUnion(
                $codebase,
                $atomic_type->as,
                $calling_class,
                $calling_class,
                null,
            );

            $as = self::replace(
                $as,
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                true,
                $add_lower_bound,
                $bound_equality_classlike,
                $depth + 1,
            );

            $atomic_type = $atomic_type->replaceAs($as);

            if ($input_type
                && !$template_result->readonly
                && (
                    $atomic_type->as->isMixed()
                    || UnionTypeComparator::canBeContainedBy(
                        $codebase,
                        $input_type,
                        $atomic_type->as,
                        false,
                        false,
                        $matching_input_keys,
                    )
                )
            ) {
                $generic_param = $input_type->getBuilder();

                if ($matching_input_keys) {
                    $generic_param_keys = array_keys($generic_param->getAtomicTypes());

                    foreach ($generic_param_keys as $atomic_key) {
                        if (!isset($matching_input_keys[$atomic_key])) {
                            $generic_param->removeType($atomic_key);
                        }
                    }
                }
                if ($add_lower_bound) {
                    return array_values($generic_param->getAtomicTypes());
                }

                $generic_param->possibly_undefined = false;
                $generic_param = $generic_param->setFromDocblock()->freeze();

                if (isset(
                    $template_result->lower_bounds[$param_name_key][$atomic_type->defining_class],
                )) {
                    $existing_lower_bounds = $template_result->lower_bounds
                        [$param_name_key]
                        [$atomic_type->defining_class];

                    $has_matching_lower_bound = false;

                    foreach ($existing_lower_bounds as $existing_lower_bound) {
                        $existing_depth = $existing_lower_bound->appearance_depth;
                        $existing_arg_offset = $existing_lower_bound->arg_offset ?? $input_arg_offset;

                        if ($existing_depth === $depth
                            && $input_arg_offset === $existing_arg_offset
                            && $existing_lower_bound->type->getId() === $generic_param->getId()
                            && $existing_lower_bound->equality_bound_classlike === $bound_equality_classlike
                        ) {
                            $has_matching_lower_bound = true;
                            break;
                        }
                    }

                    if (!$has_matching_lower_bound) {
                        $template_result->lower_bounds
                            [$param_name_key]
                            [$atomic_type->defining_class]
                            [] = new TemplateBound(
                                $generic_param,
                                $depth,
                                $input_arg_offset,
                                $bound_equality_classlike,
                            );
                    }
                } else {
                    $template_result->lower_bounds[$param_name_key][$atomic_type->defining_class] = [
                        new TemplateBound(
                            $generic_param,
                            $depth,
                            $input_arg_offset,
                            $bound_equality_classlike,
                        ),
                    ];
                }
            }

            foreach ($atomic_types as &$t) {
                if ($t instanceof TNamedObject
                    || $t instanceof TTemplateParam
                    || $t instanceof TIterable
                    || $t instanceof TObjectWithProperties
                ) {
                    $t = $t->setIntersectionTypes($extra_types);
                } elseif ($t instanceof TObject && $extra_types) {
                    $t = reset($extra_types)->setIntersectionTypes(array_slice($extra_types, 1));
                }
            }
            unset($t);

            return $atomic_types;
        }

        if ($add_lower_bound && $input_type && !$template_result->readonly) {
            $matching_input_keys = [];

            if (UnionTypeComparator::canBeContainedBy(
                $codebase,
                $input_type,
                $replacement_type,
                false,
                false,
                $matching_input_keys,
            )
            ) {
                $generic_param = $input_type->getBuilder();

                if ($matching_input_keys) {
                    $generic_param_keys = array_keys($generic_param->getAtomicTypes());

                    foreach ($generic_param_keys as $atomic_key) {
                        if (!isset($matching_input_keys[$atomic_key])) {
                            $generic_param->removeType($atomic_key);
                        }
                    }
                }
                $generic_param = $generic_param->freeze();

                $upper_bound = $template_result->upper_bounds
                    [$param_name_key]
                    [$atomic_type->defining_class] ?? null;

                if ($upper_bound) {
                    if (!UnionTypeComparator::isContainedBy(
                        $codebase,
                        $upper_bound->type,
                        $generic_param,
                    ) || !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $generic_param,
                        $upper_bound->type,
                    )) {
                        $intersection_type = Type::intersectUnionTypes(
                            $upper_bound->type,
                            $generic_param,
                            $codebase,
                        );
                    } else {
                        $intersection_type = $generic_param;
                    }

                    if ($intersection_type) {
                        $upper_bound->type = $intersection_type;
                    } else {
                        $template_result->upper_bounds_unintersectable_types[] = $upper_bound->type;
                        $template_result->upper_bounds_unintersectable_types[] = $generic_param;

                        $upper_bound->type = Type::getMixed();
                    }
                } else {
                    $template_result->upper_bounds[$param_name_key][$atomic_type->defining_class] = new TemplateBound(
                        $generic_param,
                    );
                }
            }
        }

        return [$atomic_type];
    }

    /**
     * @return non-empty-list<TClassString>
     */
    public static function handleTemplateParamClassStandin(
        TTemplateParamClass $atomic_type,
        ?Union $input_type,
        ?int $input_arg_offset,
        ?string $calling_class,
        ?string $calling_function,
        TemplateResult $template_result,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer,
        bool $replace,
        bool $add_lower_bound,
        ?string $bound_equality_classlike,
        int $depth,
        bool $was_single
    ): array {
        if ($atomic_type->defining_class === $calling_class) {
            return [$atomic_type];
        }

        $atomic_types = [];

        $as_type = $atomic_type->as_type;
        if ($input_type && !$template_result->readonly) {
            $valid_input_atomic_types = [];

            foreach ($input_type->getAtomicTypes() as $input_atomic_type) {
                if ($input_atomic_type instanceof TLiteralClassString) {
                    $valid_input_atomic_types[] = new TNamedObject(
                        $input_atomic_type->value,
                        false,
                        false,
                        [],
                        true,
                    );
                } elseif ($input_atomic_type instanceof TTemplateParamClass) {
                    $valid_input_atomic_types[] = new TTemplateParam(
                        $input_atomic_type->param_name,
                        $input_atomic_type->as_type
                            ? new Union([$input_atomic_type->as_type])
                            : ($input_atomic_type->as === 'object'
                                ? Type::getObject()
                                : Type::getMixed()),
                        $input_atomic_type->defining_class,
                        [],
                        true,
                    );
                } elseif ($input_atomic_type instanceof TClassString) {
                    if ($input_atomic_type->as_type) {
                        $valid_input_atomic_types[] = $input_atomic_type->as_type->setFromDocblock(true);
                    } elseif ($input_atomic_type->as !== 'object') {
                        $valid_input_atomic_types[] = new TNamedObject(
                            $input_atomic_type->as,
                            false,
                            false,
                            [],
                            true,
                        );
                    } else {
                        $valid_input_atomic_types[] = new TObject(true);
                    }
                } elseif ($input_atomic_type instanceof TDependentGetClass) {
                    $valid_input_atomic_types[] = new TObject(true);
                }
            }

            $generic_param = null;

            if ($valid_input_atomic_types) {
                $generic_param = new Union($valid_input_atomic_types);
            } elseif ($was_single) {
                $generic_param = Type::getMixed();
            }

            if ($as_type) {
                // sometimes templated class-strings can contain nested templates
                // in the as type that need to be resolved as well.
                $as_type_union = self::replace(
                    new Union([$as_type]),
                    $template_result,
                    $codebase,
                    $statements_analyzer,
                    $generic_param,
                    $input_arg_offset,
                    $calling_class,
                    $calling_function,
                    $replace,
                    $add_lower_bound,
                    $bound_equality_classlike,
                    $depth + 1,
                );

                $first = $as_type_union->getSingleAtomic();

                if (count($as_type_union->getAtomicTypes()) === 1 && $first instanceof TNamedObject) {
                    $as_type = $first;
                } else {
                    $as_type = null;
                }
            }

            if ($generic_param) {
                if (isset($template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class])) {
                    $template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class] = [
                        new TemplateBound(
                            Type::combineUnionTypes(
                                $generic_param,
                                self::getMostSpecificTypeFromBounds(
                                    $template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class],
                                    $codebase,
                                ),
                            ),
                            $depth,
                        ),
                    ];
                } else {
                    $template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class] = [
                        new TemplateBound(
                            $generic_param,
                            $depth,
                            $input_arg_offset,
                        ),
                    ];
                }
            }
        } else {
            $template_type = $template_result->template_types
                [$atomic_type->param_name]
                [$atomic_type->defining_class];

            foreach ($template_type->getAtomicTypes() as $template_atomic_type) {
                if ($template_atomic_type instanceof TNamedObject) {
                    $atomic_types[] = new TClassString(
                        $template_atomic_type->value,
                        $template_atomic_type,
                    );
                } elseif ($template_atomic_type instanceof TObject) {
                    $atomic_types[] = new TClassString();
                }
            }
        }

        $class_string = new TClassString($atomic_type->as, $as_type);

        if (!$atomic_types) {
            $atomic_types[] = $class_string;
        }

        return $atomic_types;
    }

    /**
     * @param  array<string, array<string, non-empty-list<TemplateBound>>>  $template_types
     */
    public static function getRootTemplateType(
        array $template_types,
        string $param_name,
        string $defining_class,
        array $visited_classes,
        ?Codebase $codebase
    ): ?Union {
        if (isset($visited_classes[$defining_class])) {
            return null;
        }

        if (isset($template_types[$param_name][$defining_class])) {
            $mapped_type = self::getMostSpecificTypeFromBounds(
                $template_types[$param_name][$defining_class],
                $codebase,
            );

            $mapped_type_atomic_types = array_values($mapped_type->getAtomicTypes());

            if (count($mapped_type_atomic_types) > 1
                || !$mapped_type_atomic_types[0] instanceof TTemplateParam
            ) {
                return $mapped_type;
            }

            $first_template = $mapped_type_atomic_types[0];

            return self::getRootTemplateType(
                $template_types,
                $first_template->param_name,
                $first_template->defining_class,
                $visited_classes + [$defining_class => true],
                $codebase,
            ) ?? $mapped_type;
        }

        return null;
    }

    /**
     * This takes a list of lower bounds and returns the most general type.
     *
     * If given a single bound that's just the type of that bound.
     *
     * If instead given a collection of lower bounds it normally returns a union of those
     * bound types.
     *
     * @param  non-empty-list<TemplateBound>  $lower_bounds
     */
    public static function getMostSpecificTypeFromBounds(array $lower_bounds, ?Codebase $codebase): Union
    {
        if (count($lower_bounds) === 1) {
            return reset($lower_bounds)->type;
        }

        usort(
            $lower_bounds,
            static fn(TemplateBound $bound_a, TemplateBound $bound_b): int => $bound_b->appearance_depth <=> $bound_a->appearance_depth,
        );

        $current_depth = null;
        $current_type = null;
        $had_invariant = false;
        $last_arg_offset = -1;

        foreach ($lower_bounds as $template_bound) {
            if ($current_depth === null) {
                $current_depth = $template_bound->appearance_depth;
            } elseif ($current_depth !== $template_bound->appearance_depth && $current_type) {
                if (!$current_type->isNever()
                    && ($had_invariant || $last_arg_offset === $template_bound->arg_offset)
                ) {
                    // escape switches when matching on invariant generic params
                    // and when matching
                    break;
                }

                $current_depth = $template_bound->appearance_depth;
            }

            $had_invariant = $had_invariant ?: $template_bound->equality_bound_classlike !== null;

            $current_type = Type::combineUnionTypes(
                $current_type,
                $template_bound->type,
                $codebase,
            );

            $last_arg_offset = $template_bound->arg_offset;
        }

        return $current_type ?? Type::getMixed();
    }

    /**
     * @param TGenericObject|TNamedObject|TIterable $input_type_part
     * @param TGenericObject|TIterable $container_type_part
     * @psalm-external-mutation-free
     * @return list<Union>
     */
    public static function getMappedGenericTypeParams(
        Codebase $codebase,
        Atomic $input_type_part,
        Atomic $container_type_part,
        ?array &$container_type_params_covariant = null
    ): array {
        if ($input_type_part instanceof TGenericObject || $input_type_part instanceof TIterable) {
            $input_type_params = $input_type_part->type_params;
        } elseif ($codebase->classlike_storage_provider->has($input_type_part->value)) {
            $class_storage = $codebase->classlike_storage_provider->get($input_type_part->value);

            $container_class = $container_type_part->value;

            if (strtolower($input_type_part->value) === strtolower($container_type_part->value)) {
                $input_type_params = $class_storage->getClassTemplateTypes();
            } elseif (!empty($class_storage->template_extended_params[$container_class])) {
                $input_type_params = array_values($class_storage->template_extended_params[$container_class]);
            } else {
                $input_type_params = array_fill(0, count($class_storage->template_types ?? []), Type::getMixed());
            }
        } else {
            $input_type_params = [];
        }

        $input_class_storage = $codebase->classlike_storage_provider->has($input_type_part->value)
            ? $codebase->classlike_storage_provider->get($input_type_part->value)
            : null;

        $container_type_params_covariant = $codebase->classlike_storage_provider->has($container_type_part->value)
            ? $codebase->classlike_storage_provider->get($container_type_part->value)->template_covariants
            : null;

        if ($input_type_part->value !== $container_type_part->value
            && $input_class_storage
        ) {
            $input_template_types = $input_class_storage->template_types;
            $i = 0;

            $replacement_templates = [];

            if ($input_template_types
                && (!$container_type_part instanceof TGenericObject || !$container_type_part->remapped_params)
            ) {
                foreach ($input_template_types as $template_name => $_) {
                    if (!isset($input_type_params[$i])) {
                        break;
                    }

                    $replacement_templates[$template_name][$input_type_part->value] = $input_type_params[$i];

                    $i++;
                }
            }

            $template_extends = $input_class_storage->template_extended_params;

            $container_type_part_value = $container_type_part->value === 'iterable'
                ? 'Traversable'
                : $container_type_part->value;

            if (isset($template_extends[$container_type_part_value])) {
                $params = $template_extends[$container_type_part_value];

                $new_input_params = [];

                foreach ($params as $extended_input_param_type) {
                    $new_input_param = null;

                    foreach ($extended_input_param_type->getAtomicTypes() as $extended_template) {
                        $extended_templates = $extended_template instanceof TTemplateParam
                            ? array_values(
                                array_filter(
                                    Methods::getExtendedTemplatedTypes($extended_template, $template_extends),
                                    static fn(Atomic $a) => $a instanceof TTemplateParam,
                                ),
                            )
                            : [];

                        $candidate_param_types = [];

                        if ($extended_templates) {
                            foreach ($extended_templates as $template) {
                                if (!isset(
                                    $input_class_storage->template_types
                                        [$template->param_name]
                                        [$template->defining_class],
                                )) {
                                    continue;
                                }

                                $old_params_offset = (int) array_search(
                                    $template->param_name,
                                    array_keys($input_class_storage->template_types),
                                    true,
                                );

                                $candidate_param_types[] = ($input_type_params[$old_params_offset] ?? Type::getMixed())
                                    ->setProperties(['from_template_default' => true]);
                            }
                        }

                        $new_input_param = Type::combineUnionTypes(
                            $new_input_param,
                            $candidate_param_types
                                ? Type::combineUnionTypeArray($candidate_param_types, $codebase)
                                : new Union([$extended_template], ['from_template_default' => true]),
                        );
                    }

                    $new_input_param = TemplateInferredTypeReplacer::replace(
                        $new_input_param,
                        new TemplateResult([], $replacement_templates),
                        $codebase,
                    );

                    $new_input_params[] = $new_input_param;
                }

                $input_type_params = $new_input_params;
            }
        }

        return $input_type_params;
    }
}
