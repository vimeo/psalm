<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use InvalidArgumentException;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\Methods;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Internal\Type\Comparator\KeyedArrayComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\TypeVisitor\ContainsTemplateReaderVisitor;
use Psalm\Internal\TypeVisitor\TypeVariableResolver;
use Psalm\Storage\Capabilities;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TDependentGetClass;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIntersection;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
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
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\MutableUnion;
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
use function max;
use function reset;
use function str_starts_with;
use function strcspn;
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
        int $depth = 1,
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
        int $depth = 1,
    ): Union {
        $original_atomic_types = $union_type->getAtomicTypes();

        // input atoms that share a key with a union atom are matched by that atom, so they
        // never become part of the type inferred for the other atoms, e.g. `null` for `T|null`
        $other_input_type = $input_type;

        if ($input_type && !$input_type->isSingle()) {
            $new_input_type = $input_type->getBuilder();

            foreach ($original_atomic_types as $key => $_) {
                if ($new_input_type->hasType($key)) {
                    $new_input_type->removeType($key);
                }
            }

            $other_input_type = $new_input_type->isUnionEmpty() ? null : $new_input_type->freeze();
        }

        // a bare template is inferred after the other atoms, from the input atoms none of them inferred it from:
        // each input atom says what the template is through the one atom it matches (`list<int>` through `list<T>`
        // for `T|list<T>`), and what the atoms say is joined
        $templates = [];
        $has_intersected_template = false;

        if ($input_type && !$template_result->readonly && count($original_atomic_types) > 1) {
            foreach ($original_atomic_types as $key => $atomic_type) {
                if ($atomic_type instanceof TTemplateParam
                    && isset($template_result->template_types[$atomic_type->param_name][$atomic_type->defining_class])
                ) {
                    if ($atomic_type->extra_types) {
                        $has_intersected_template = true;
                    } else {
                        $templates[$key] = $atomic_type;
                    }
                }
            }
        }

        // `T&Countable` records its bounds apart from `T`, so what it matches would not reach `T` if the other
        // atoms took it: every atom gets the input no atom matches by key
        if ($has_intersected_template && $input_type !== $other_input_type) {
            if (!$other_input_type) {
                return $union_type;
            }

            $input_type = $other_input_type;
        }

        $template_readers = [];
        $inferring_input_keys = [];
        $rejected_input_types = [];
        $replaced_atomic_types = [];
        $had_template = false;

        foreach ($original_atomic_types as $key => $atomic_type) {
            if (isset($templates[$key])) {
                continue;
            }

            $matches_input_key = $input_type && $input_type->hasType($key);

            // nothing is left to infer the other atoms from, so keep them as they are
            if (!$matches_input_key && $input_type && !$other_input_type) {
                $replaced_atomic_types[$key] = [$atomic_type];
                continue;
            }

            // `key-of<T>` and the like read what the bare templates were inferred as, so they wait for them
            if ($templates) {
                $template_reader_visitor = new ContainsTemplateReaderVisitor($templates);
                $template_reader_visitor->traverse($atomic_type);

                if ($template_reader_visitor->matches()) {
                    $template_readers[$key] = $atomic_type;
                    continue;
                }
            }

            $replaced_atomic_types[$key] = self::handleAtomicStandinBeforeTemplates(
                $atomic_type,
                $key,
                $template_result,
                $codebase,
                $statements_analyzer,
                $matches_input_key ? $input_type : $other_input_type,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_lower_bound,
                $bound_equality_classlike,
                $depth,
                count($original_atomic_types) === 1,
                $had_template,
                $templates,
                $inferring_input_keys,
                $rejected_input_types,
            );
        }

        $template_input_type = self::getTemplateInputType(
            $other_input_type,
            $rejected_input_types,
            $inferring_input_keys,
        );

        foreach ($templates + $template_readers as $key => $atomic_type) {
            if (!isset($templates[$key])) {
                $atomic_input_type = $input_type && $input_type->hasType($key) ? $input_type : $other_input_type;
            } elseif (!$other_input_type && !$rejected_input_types) {
                // every input atom shares a key with another atom, so keep it as it is
                $replaced_atomic_types[$key] = [$atomic_type];
                continue;
            } else {
                $atomic_input_type = $template_input_type;
            }

            $replaced_atomic_types[$key] = self::handleAtomicStandin(
                $atomic_type,
                $key,
                $template_result,
                $codebase,
                $statements_analyzer,
                $atomic_input_type,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_lower_bound,
                $bound_equality_classlike,
                $depth,
                false,
                $had_template,
            );
        }

        $atomic_types = [];

        foreach ($original_atomic_types as $key => $_) {
            foreach ($replaced_atomic_types[$key] as $replaced_atomic_type) {
                $atomic_types[] = $replaced_atomic_type;
            }
        }

        // the result keeps the parent nodes: this also runs on argument types, whose data flow must survive
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
                    'parent_nodes' => $union_type->parent_nodes,
                ]);
            }

            return new Union($atomic_types, [
                'ignore_nullable_issues' => $union_type->ignore_nullable_issues,
                'ignore_falsable_issues' => $union_type->ignore_falsable_issues,
                'possibly_undefined' => $union_type->possibly_undefined,
                'had_template' => $had_template,
                'parent_nodes' => $union_type->parent_nodes,
            ]);
        }

        return $union_type;
    }

    /**
     * @param array<string, true> $matched_input_keys set to the keys of the input atoms the atom matched
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
        bool &$had_template,
        array &$matched_input_keys = [],
    ): array {
        // the name, without the type parameters or purity arguments
        $key = substr($key, 0, strcspn($key, '<['));

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

        if ($atomic_type instanceof TIntersection) {
            // the argument has the type of each member of the intersection
            $intersected_types = [];

            foreach ($atomic_type->types as $intersected_type) {
                $intersected_types[] = self::replace(
                    $intersected_type,
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
            }

            return [$atomic_type->setTypes($intersected_types)];
        }

        $matching_atomic_types = [];

        if ($input_type && !$input_type->hasMixed()) {
            $matching_atomic_types = self::findMatchingAtomicTypesForTemplate(
                $atomic_type,
                $key,
                $codebase,
                $statements_analyzer,
                $input_type,
                $matched_input_keys,
            );
        }

        if (!$matching_atomic_types) {
            $replaced_atomic_type = $atomic_type->replaceTemplateTypesWithStandins(
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

            // not where another alternative stays in the union: what it holds is not given by the inferred template
            if ($was_single && $replaced_atomic_type !== $atomic_type && !$template_result->readonly) {
                $had_template = true;
            }

            return [$replaced_atomic_type];
        }

        $atomic_types = [];

        foreach ($matching_atomic_types as $matching_atomic_type) {
            $replaced_atomic_type = $atomic_type->replaceTemplateTypesWithStandins(
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

            // not where another alternative stays in the union: what it holds is not given by the inferred template
            if ($was_single && $replaced_atomic_type !== $atomic_type && !$template_result->readonly) {
                $had_template = true;
            }

            $atomic_types[] = $replaced_atomic_type;
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
     * @param array<string, true> $matched_input_keys set to the keys of the input atoms that match
     * @return list<Atomic>
     */
    private static function findMatchingAtomicTypesForTemplate(
        Atomic $base_type,
        string $key,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer,
        Union $input_type,
        array &$matched_input_keys = [],
    ): array {
        $matching_atomic_types = [];

        foreach ($input_type->getAtomicTypes() as $input_type_key => $atomic_input_type) {
            // unset below unless a match continues the loop
            $matched_input_keys[$input_type_key] = true;

            $input_key = substr($input_type_key, 0, strcspn($input_type_key, '<['));

            if ($input_key === $key
                || ($atomic_input_type instanceof TClosure && $base_type instanceof TClosure)
                || ($atomic_input_type instanceof TCallable && $base_type instanceof TCallable)
                || ($atomic_input_type instanceof TClosure && $base_type instanceof TCallable)
                || (($atomic_input_type instanceof TArray || $atomic_input_type instanceof TKeyedArray)
                    && $key === 'iterable')
                || str_starts_with($input_key, $key . '&')
            ) {
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
                } catch (InvalidArgumentException) {
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

                $matching_object = self::getObjectMatchingBase($codebase, $atomic_input_type, $base_type);

                if ($matching_object) {
                    $matching_atomic_types[$matching_object->getId()] = $matching_object;
                    continue;
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
                } else {
                    unset($matched_input_keys[$input_type_key]);
                }

                continue;
            }

            if ($atomic_input_type instanceof TTemplateParam) {
                $template_matching_atomic_types = self::findMatchingAtomicTypesForTemplate(
                    $base_type,
                    $key,
                    $codebase,
                    $statements_analyzer,
                    $atomic_input_type->as,
                );

                if ($template_matching_atomic_types) {
                    $matching_atomic_types = array_merge($matching_atomic_types, $template_matching_atomic_types);
                    continue;
                }
            }

            unset($matched_input_keys[$input_type_key]);
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
        bool &$had_template,
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

                    // a binding that is only read (class templates fixed by the receiver) is the actual
                    // type, not a stand-in for what the arguments may still infer
                    if (!$template_result->readonly) {
                        $had_template = true;
                    }
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

            // a type variable in the template's bound resolves to its
            // construction-site inference: whether an input can bind the
            // template is a structural question this gate has to answer now,
            // not a constraint that can wait for reconciliation
            $as_resolver = new TypeVariableResolver($codebase);
            $as_resolver->traverse($as);

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
                ) && !self::isConstraintFallbackOnly(
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

            // a type variable standing in for the template itself keeps
            // recording bounds, but one nested inside the replacement is
            // structural: it resolves to its construction-site inference
            $resolved_atomic_types = [];

            foreach ($atomic_types as $candidate_atomic_type) {
                if ($candidate_atomic_type instanceof TTypeVariable) {
                    $resolved_atomic_types[] = $candidate_atomic_type;
                    continue;
                }

                $candidate_union = new Union([$candidate_atomic_type]);
                $candidate_resolver = new TypeVariableResolver($codebase);
                $candidate_resolver->traverse($candidate_union);

                foreach ($candidate_union->getAtomicTypes() as $resolved_atomic_type) {
                    $resolved_atomic_types[] = $resolved_atomic_type;
                }
            }

            $atomic_types = $resolved_atomic_types;

            foreach ($atomic_types as &$t) {
                if ($t instanceof TNamedObject
                    || $t instanceof TTemplateParam
                    || $t instanceof TIterable
                    || $t instanceof TObjectWithProperties
                ) {
                    if ($extra_types) {
                        $t = $t->setIntersectionTypes([...$t->getIntersectionTypes(), ...$extra_types]);
                    }
                } elseif ($t instanceof TObject && $extra_types) {
                    $t = reset($extra_types)->setIntersectionTypes(array_slice($extra_types, 1));
                }
            }
            unset($t);

            return $atomic_types;
        }

        if ($add_lower_bound && $input_type && !$template_result->readonly) {
            $matching_input_keys = [];

            // a class template already standing for a type variable (minted at
            // the construction site, e.g. `new Table($items)`) cannot answer
            // this structural question itself: it is resolved to its
            // construction-site inference for the gate, while the upper bound
            // recorded below still reconciles against the variable's bounds
            $gate_replacement_type = $replacement_type;

            foreach ($replacement_type->getAtomicTypes() as $replacement_atomic_type) {
                if ($replacement_atomic_type instanceof TTypeVariable) {
                    $gate_resolver = new TypeVariableResolver($codebase);
                    $gate_resolver->traverse($gate_replacement_type);
                    break;
                }
            }

            if (UnionTypeComparator::canBeContainedBy(
                $codebase,
                $input_type,
                $gate_replacement_type,
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
        bool $was_single,
    ): array {
        if ($atomic_type->defining_class === $calling_class) {
            return [$atomic_type];
        }

        $atomic_types = [];

        $as_type = $atomic_type->as_type;
        if ($input_type && !$template_result->readonly) {
            $valid_input_atomic_types = [];

            // a template bounded by a class-string (the one generated for a parameter used in a conditional
            // return type) is as good as its bound
            $input_atomic_types = [];

            foreach ($input_type->getAtomicTypes() as $input_atomic_type) {
                if ($input_atomic_type instanceof TTemplateParam) {
                    foreach ($input_atomic_type->as->getAtomicTypes() as $bound_atomic_type) {
                        $input_atomic_types[] = $bound_atomic_type;
                    }
                } else {
                    $input_atomic_types[] = $input_atomic_type;
                }
            }

            foreach ($input_atomic_types as $input_atomic_type) {
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
                    // what get_class($x) is known to be: the class of $x, or any object if that is not known
                    if ($input_atomic_type->as_type->isMixed() || $input_atomic_type->as_type->hasObject()) {
                        $valid_input_atomic_types[] = new TObject(true);
                    } else {
                        foreach ($input_atomic_type->as_type->getAtomicTypes() as $as_atomic_type) {
                            $valid_input_atomic_types[] = $as_atomic_type;
                        }
                    }
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
                if (isset($template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class])
                    && !self::isConstraintFallbackOnly(
                        $template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class],
                    )
                ) {
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
     * @param array<string, array<string, non-empty-list<TemplateBound>>>  $template_types
     * @psalm-external-mutation-free
     */
    public static function getRootTemplateType(
        array $template_types,
        string $param_name,
        string $defining_class,
        array $visited_classes,
        ?Codebase $codebase,
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
     * handleAtomicStandin() for an atom of a union with bare templates. The input atoms the atom inferred the
     * templates from are added to $inferring_input_keys. A matched input atom the atom does not accept, like
     * `list<int>` for `array<string, T>`, says nothing about the templates through the atom: it is added to
     * $rejected_input_types instead, and the atom is replaced again without it, from the inference state before.
     * Without $replace, the atom only drops the input atoms it would reject when replacing.
     *
     * @param array<string, TTemplateParam> $templates
     * @param array<string, true> $inferring_input_keys
     * @param array<string, Atomic> $rejected_input_types
     * @return list<Atomic>
     */
    private static function handleAtomicStandinBeforeTemplates(
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
        bool &$had_template,
        array $templates,
        array &$inferring_input_keys,
        array &$rejected_input_types,
    ): array {
        $inference_state = $templates ? self::getInferenceState($template_result) : null;

        if (!$replace && $inference_state && $input_type) {
            $input_type = self::getInputTypeAcceptedWhenReplacing(
                $atomic_type,
                $key,
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $add_lower_bound,
                $bound_equality_classlike,
                $depth,
                $inference_state,
            );

            // what it rejects is not given to the bare templates either: where they take upper bounds, from the
            // params of a callable, it would widen them
            return self::handleAtomicStandin(
                $atomic_type,
                $key,
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                false,
                $add_lower_bound,
                $bound_equality_classlike,
                $depth,
                $was_single,
                $had_template,
            );
        }

        while (true) {
            $matched_input_keys = [];

            $replaced_atomic_types = self::handleAtomicStandin(
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
                $was_single,
                $had_template,
                $matched_input_keys,
            );

            if (!$inference_state
                || !$templates
                || !$input_type
                || !$matched_input_keys
                || !self::hasInferenceStateChanged($template_result, $inference_state)
            ) {
                return $replaced_atomic_types;
            }

            $atom_rejected_input_types = self::getRejectedInputTypes(
                $codebase,
                $input_type,
                $matched_input_keys,
                $replaced_atomic_types,
            );

            if (!$atom_rejected_input_types) {
                if (self::hasAddedTemplateBounds($template_result, $templates, $inference_state[0])) {
                    self::raiseAddedTemplateBounds($template_result, $templates, $inference_state[0], $depth);

                    $inferring_input_keys += $matched_input_keys;
                }

                return $replaced_atomic_types;
            }

            $rejected_input_types += $atom_rejected_input_types;

            self::restoreInferenceState($template_result, $inference_state);

            $new_input_type = $input_type->getBuilder();

            foreach ($atom_rejected_input_types as $input_key => $_) {
                $new_input_type->removeType($input_key);
            }

            $input_type = $new_input_type->isUnionEmpty() ? null : $new_input_type->freeze();
        }
    }

    /**
     * The input without the atoms the atom would reject when replacing: without replacing, the atom keeps its
     * templates, which no concrete input atom is contained by
     *
     * @param array{
     *     array<string, array<string, non-empty-list<TemplateBound>>>,
     *     array<string, array<string, TemplateBound>>,
     *     list<Union>,
     *     list<array{TemplateBound, Union}>,
     * } $inference_state
     */
    private static function getInputTypeAcceptedWhenReplacing(
        Atomic $atomic_type,
        string $key,
        TemplateResult $template_result,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer,
        Union $input_type,
        ?int $input_arg_offset,
        ?string $calling_class,
        ?string $calling_function,
        bool $add_lower_bound,
        ?string $bound_equality_classlike,
        int $depth,
        array $inference_state,
    ): ?Union {
        $matched_input_keys = [];
        $had_template = false;

        $replaced_atomic_types = self::handleAtomicStandin(
            $atomic_type,
            $key,
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
            $depth,
            false,
            $had_template,
            $matched_input_keys,
        );

        $rejected_input_types = $matched_input_keys && self::hasInferenceStateChanged($template_result, $inference_state)
            ? self::getRejectedInputTypes($codebase, $input_type, $matched_input_keys, $replaced_atomic_types)
            : [];

        self::restoreInferenceState($template_result, $inference_state);

        if (!$rejected_input_types) {
            return $input_type;
        }

        $new_input_type = $input_type->getBuilder();

        foreach ($rejected_input_types as $input_key => $_) {
            $new_input_type->removeType($input_key);
        }

        return $new_input_type->isUnionEmpty() ? null : $new_input_type->freeze();
    }

    /**
     * What the bare templates of a union are inferred from: the input atoms the other atoms did not infer them from
     *
     * @param array<string, Atomic> $rejected_input_types
     * @param array<string, true> $inferring_input_keys
     */
    private static function getTemplateInputType(
        ?Union $other_input_type,
        array $rejected_input_types,
        array $inferring_input_keys,
    ): ?Union {
        if (!$rejected_input_types && !$inferring_input_keys) {
            return $other_input_type;
        }

        $template_input_type = $other_input_type?->getBuilder();

        // an input atom rejected by an atom with the same key was subtracted from the other input
        foreach ($rejected_input_types as $rejected_input_type) {
            if ($template_input_type) {
                $template_input_type->addType($rejected_input_type);
            } else {
                $template_input_type = new MutableUnion([$rejected_input_type]);
            }
        }

        if (!$template_input_type) {
            return null;
        }

        foreach ($inferring_input_keys as $input_key => $_) {
            if ($template_input_type->hasType($input_key)) {
                $template_input_type->removeType($input_key);
            }
        }

        return $template_input_type->isUnionEmpty() ? null : $template_input_type->freeze();
    }

    /**
     * What replacing an atom can change: the bound lists, the unintersectable upper bounds, and the types of the
     * bounds, which are narrowed in place
     *
     * @return array{
     *     array<string, array<string, non-empty-list<TemplateBound>>>,
     *     array<string, array<string, TemplateBound>>,
     *     list<Union>,
     *     list<array{TemplateBound, Union}>,
     * }
     * @psalm-capabilities read-props
     */
    private static function getInferenceState(TemplateResult $template_result): array
    {
        $bound_types = [];

        foreach ($template_result->lower_bounds as $lower_bounds_by_class) {
            foreach ($lower_bounds_by_class as $lower_bounds) {
                foreach ($lower_bounds as $lower_bound) {
                    $bound_types[] = [$lower_bound, $lower_bound->type];
                }
            }
        }

        foreach ($template_result->upper_bounds as $upper_bounds_by_class) {
            foreach ($upper_bounds_by_class as $upper_bound) {
                $bound_types[] = [$upper_bound, $upper_bound->type];
            }
        }

        return [
            $template_result->lower_bounds,
            $template_result->upper_bounds,
            $template_result->upper_bounds_unintersectable_types,
            $bound_types,
        ];
    }

    /**
     * @param array{
     *     array<string, array<string, non-empty-list<TemplateBound>>>,
     *     array<string, array<string, TemplateBound>>,
     *     list<Union>,
     *     list<array{TemplateBound, Union}>,
     * } $inference_state
     * @psalm-capabilities read-props
     */
    private static function hasInferenceStateChanged(TemplateResult $template_result, array $inference_state): bool
    {
        if ($template_result->lower_bounds !== $inference_state[0]
            || $template_result->upper_bounds !== $inference_state[1]
            || $template_result->upper_bounds_unintersectable_types !== $inference_state[2]
        ) {
            return true;
        }

        foreach ($inference_state[3] as [$bound, $type]) {
            if ($bound->type !== $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{
     *     array<string, array<string, non-empty-list<TemplateBound>>>,
     *     array<string, array<string, TemplateBound>>,
     *     list<Union>,
     *     list<array{TemplateBound, Union}>,
     * } $inference_state
     */
    private static function restoreInferenceState(TemplateResult $template_result, array $inference_state): void
    {
        [
            $template_result->lower_bounds,
            $template_result->upper_bounds,
            $template_result->upper_bounds_unintersectable_types,
        ] = $inference_state;

        foreach ($inference_state[3] as [$bound, $type]) {
            $bound->type = $type;
        }
    }

    /**
     * @param non-empty-array<string, TTemplateParam> $templates
     * @param array<string, array<string, non-empty-list<TemplateBound>>> $lower_bounds_before
     * @psalm-capabilities read-props
     */
    private static function hasAddedTemplateBounds(
        TemplateResult $template_result,
        array $templates,
        array $lower_bounds_before,
    ): bool {
        foreach ($templates as $template) {
            if (($template_result->lower_bounds[$template->param_name][$template->defining_class] ?? [])
                !== ($lower_bounds_before[$template->param_name][$template->defining_class] ?? [])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param non-empty-array<string, true> $matched_input_keys
     * @param list<Atomic> $replaced_atomic_types what the atom that matched them was replaced with
     * @return array<string, Atomic> the matched input atoms none of the replaced atoms contains
     */
    private static function getRejectedInputTypes(
        Codebase $codebase,
        Union $input_type,
        array $matched_input_keys,
        array $replaced_atomic_types,
    ): array {
        $input_atomic_types = $input_type->getAtomicTypes();
        $rejected_input_types = [];

        foreach ($matched_input_keys as $input_key => $_) {
            $input_atomic_type = $input_atomic_types[$input_key];

            foreach ($replaced_atomic_types as $replaced_atomic_type) {
                if (AtomicTypeComparator::isContainedBy($codebase, $input_atomic_type, $replaced_atomic_type)) {
                    continue 2;
                }
            }

            $rejected_input_types[$input_key] = $input_atomic_type;
        }

        return $rejected_input_types;
    }

    /**
     * Moves the lower bounds an atom of a union added for the bare templates of the union to the depth of the union,
     * so they join, not outrank, those of the other atoms. Of those the atom added, only the deepest are kept, as
     * getMostSpecificTypeFromBounds() does for one argument: the atom matched its input there.
     *
     * @param non-empty-array<string, TTemplateParam> $templates
     * @param array<string, array<string, non-empty-list<TemplateBound>>> $lower_bounds_before
     */
    private static function raiseAddedTemplateBounds(
        TemplateResult $template_result,
        array $templates,
        array $lower_bounds_before,
        int $depth,
    ): void {
        foreach ($templates as $template) {
            $lower_bounds = $template_result->lower_bounds[$template->param_name][$template->defining_class] ?? [];
            $template_lower_bounds_before = $lower_bounds_before[$template->param_name][$template->defining_class] ?? [];
            $added_lower_bounds = [];
            $kept_depth = null;

            foreach ($lower_bounds as $offset => $lower_bound) {
                if (in_array($lower_bound, $template_lower_bounds_before, true)) {
                    continue;
                }

                $added_lower_bounds[$offset] = $lower_bound;

                if (!$lower_bound->type->isNever()) {
                    $kept_depth = max($kept_depth ?? 0, $lower_bound->appearance_depth);
                }
            }

            if (!$added_lower_bounds) {
                continue;
            }

            foreach ($added_lower_bounds as $offset => $lower_bound) {
                if ($kept_depth !== null && $lower_bound->appearance_depth < $kept_depth) {
                    unset($lower_bounds[$offset]);
                } else {
                    $lower_bound->appearance_depth = $depth;
                }
            }

            $template_result->lower_bounds[$template->param_name][$template->defining_class]
                = array_values($lower_bounds);
        }
    }

    /**
     * A placeholder written after an argument that bound nothing must be
     * replaced, not merged, by a real bound.
     *
     * @param non-empty-list<TemplateBound> $lower_bounds
     * @psalm-capabilities read-props
     */
    private static function isConstraintFallbackOnly(array $lower_bounds): bool
    {
        foreach ($lower_bounds as $lower_bound) {
            if (!$lower_bound->from_constraint_fallback) {
                return false;
            }
        }

        return true;
    }

    /**
     * This takes a list of lower bounds and returns the most general type.
     *
     * If given a single bound that's just the type of that bound.
     *
     * If instead given a collection of lower bounds it normally returns a union of those
     * bound types.
     *
     * @param non-empty-list<TemplateBound>  $lower_bounds
     * @psalm-external-mutation-free
     */
    public static function getMostSpecificTypeFromBounds(array $lower_bounds, ?Codebase $codebase): Union
    {
        if (count($lower_bounds) === 1) {
            return reset($lower_bounds)->type;
        }

        // a purity template must allow everything each position it was inferred from requires, however deep
        if (self::areAllPurityBounds($lower_bounds)) {
            $joined = null;

            foreach ($lower_bounds as $template_bound) {
                $joined = Type::combineUnionTypes($joined, $template_bound->type, $codebase);
            }

            return $joined;
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
     * Whether the bounds are all capability sets (or purity templates), i.e. those of a purity template
     *
     * @param non-empty-list<TemplateBound> $lower_bounds
     * @psalm-mutation-free
     */
    private static function areAllPurityBounds(array $lower_bounds): bool
    {
        foreach ($lower_bounds as $template_bound) {
            if (!Capabilities::isPurityArgument($template_bound->type)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The object as the base it is matched against, if its class extends it: with the params that the templates of
     * the base map to for this object, an object without type params leaving them at those of its class.
     *
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    private static function getObjectMatchingBase(
        Codebase $codebase,
        TNamedObject $atomic_input_type,
        TNamedObject $base_type,
    ): ?TNamedObject {
        try {
            $classlike_storage = $codebase->classlike_storage_provider->get($atomic_input_type->value);

            if ($atomic_input_type instanceof TGenericObject
                && isset($classlike_storage->template_extended_params[$base_type->value])
            ) {
                return $atomic_input_type;
            }

            if (!empty($classlike_storage->template_extended_params[$base_type->value])) {
                /** @var non-empty-list<Union> $params */
                $params = self::getMappedGenericTypeParams($codebase, $atomic_input_type, $base_type);

                return new TGenericObject($base_type->value, $params);
            }

            if (in_array('Traversable', $classlike_storage->class_implements)
                && $base_type->value === 'Iterator'
            ) {
                return $atomic_input_type;
            }
        } catch (InvalidArgumentException) {
            // do nothing
        }

        return null;
    }

    /**
     * @param TGenericObject|TNamedObject|TIterable $input_type_part
     * @param TGenericObject|TNamedObject|TIterable $container_type_part
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     * @return list<Union>
     */
    public static function getMappedGenericTypeParams(
        Codebase $codebase,
        Atomic $input_type_part,
        Atomic $container_type_part,
        ?array &$container_type_params_covariant = null,
    ): array {
        if ($input_type_part instanceof TGenericObject || $input_type_part instanceof TIterable) {
            $input_type_params = $input_type_part->type_params;

            // left-out templates (`Foo<A>`, purity ones of `Iterator<int, int>`) take their default, never mixed
            if ($input_type_part instanceof TGenericObject
                && $codebase->classlike_storage_provider->has($input_type_part->value)
            ) {
                $input_class_storage = $codebase->classlike_storage_provider->get($input_type_part->value);
                $input_type_params = TemplateDefaults::fillTypeArguments(
                    $input_type_params,
                    $input_class_storage,
                    $codebase,
                    $input_type_part->from_docblock,
                );

                foreach (array_keys($input_class_storage->template_types ?? []) as $i => $template_name) {
                    if ($i >= count($input_type_params)
                        && isset($input_class_storage->purity_templates[$template_name])
                        && isset($input_class_storage->template_defaults[$template_name])
                    ) {
                        $input_type_params[] = $input_class_storage->template_defaults[$template_name];
                    }
                }
            }
        } elseif ($codebase->classlike_storage_provider->has($input_type_part->value)) {
            $class_storage = $codebase->classlike_storage_provider->get($input_type_part->value);

            $container_class = $container_type_part->value;

            if (strtolower($input_type_part->value) === strtolower($container_type_part->value)) {
                $input_type_params = $class_storage->getClassTemplateTypes();
            } elseif (!empty($class_storage->template_extended_params[$container_class])) {
                // the params of the container are mapped below from those of the class, which an
                // object without type params leaves at its templates
                $input_type_params = $class_storage->template_types !== null && $class_storage->template_types !== []
                    ? self::getOwnTemplateParams($class_storage->template_types)
                    : array_values($class_storage->template_extended_params[$container_class]);
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

    /**
     * The templates of a class as its type params: what an object of the class without type params
     * has them bound to.
     *
     * @param array<string, non-empty-array<string, Union>> $template_types
     * @return list<Union>
     * @psalm-pure
     */
    private static function getOwnTemplateParams(array $template_types): array
    {
        $params = [];

        foreach ($template_types as $template_name => $type_map) {
            foreach ($type_map as $defining_class => $bound) {
                $params[] = new Union([new TTemplateParam($template_name, $bound, $defining_class)]);
                break;
            }
        }

        return $params;
    }
}
