<?php

declare(strict_types=1);

namespace Psalm\Type;

use Psalm\Codebase;
use Psalm\Exception\RecursivePropertiesOfCycleException;
use Psalm\Exception\RecursivePropertiesOfIntersectionException;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TPropertiesOf;
use Psalm\Type\Atomic\TRecursivePropertiesOf;
use Psalm\Type\Atomic\TTemplateParam;

use function array_pop;
use function array_search;
use function array_slice;
use function array_values;
use function assert;
use function count;
use function implode;

/**
 * @internal
 * @psalm-immutable
 * @psalm-internal Psalm\Internal\Type\TypeExpander
 * @psalm-suppress InternalClass,InternalMethod This class is calling its own methods.
 */
final class RecursivePropertiesOfExpander
{
    /**
     * Expand `recursive-properties-of<T>`, where `T` is a union.
     *
     * Unions expand into the union of `recursive-properties-of<T>` applied to
     * each part separately. E.g. `recursive-properties-of<T|U>` becomes
     * `recursive-properties-of<T>|recursive-properties-of<U>`.
     *
     * @param list<TNamedObject> $expanded_parents
     * @psalm-suppress InaccessibleProperty We just created the type.
     */
    public static function expandUnion(
        Codebase $codebase,
        Union $return_type,
        ?string $self_class,
        string|TNamedObject|TTemplateParam|null $static_class_type,
        ?string $parent_class,
        bool $evaluate_class_constants = true,
        bool $evaluate_conditional_types = false,
        bool $final = false,
        bool $expand_generic = false,
        bool $expand_templates = false,
        bool $throw_on_unresolvable_constant = false,
        array $expanded_parents = [],
    ): Union {
        $new_return_type_parts = [];

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
                $expanded_parents,
            );

            $new_return_type_parts = [...$new_return_type_parts, ...$parts];
        }

        $fleshed_out_type = TypeCombiner::combine(
            $new_return_type_parts,
            $codebase,
        );

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
     * Expand `recursive-properties-of<T>`, where `T` is an atomic type.
     *
     * - Arrays, lists, and objects with properties expand to array shapes,
     *   with `recursive-properties-of<T>` applied to their value parameters.
     * - `iterable`, equivalent to `array|\Traversable`, expands to `array`.
     *     - `array` expands to `array` (`recursive-properties-of<mixed>` is
     *       `mixed`), and `\Traversable` expands to `array<never, never>`. The
     *       union `array|array<never, never>` simplifies to `array`.
     * - Named objects are expanded to `recursive-properties-of<properties-of<T>>`.
     * - Templates are not expanded, returning `recursive-properties-of<T>`,
     *   where `T` is the original template. This defers expansion until the
     *   template can be realized.
     * - If another `recursive-properties-of<T>` is encountered during
     *   expansion, it transparently expands its parameter.
     * - All other types return themselves.
     *
     * When `recursive-properties-of<T>` attempts to expand a cyclic reference,
     * a `RecursivePropertiesOfCycleException` is raised in lieu of running into
     * a stack overflow.
     *
     * The behaviour of intersection types within `recursive-properties-of<T>` is
     * not defined. If an intersection type is encountered during expansion, a
     * `RecursivePropertiesOfIntersectionException` is thrown.
     *
     * @param list<TNamedObject> $expanded_parents
     * @return (
     *     $return_type is TIterable              ? list{TArray} :
     *     $return_type is TNamedObject           ? list{TKeyedArray|TPropertiesOf} :
     *     $return_type is TObject                ? list{TArray|TKeyedArray} :
     *     $return_type is TRecursivePropertiesOf ? non-empty-list<Atomic> :
     *     $return_type is TTemplateParam         ? list{TRecursivePropertiesOf} :
     *     list{$return_type}
     * )
     */
    public static function expandAtomic(
        Codebase $codebase,
        Atomic $return_type,
        ?string $self_class,
        string|TNamedObject|TTemplateParam|null $static_class_type,
        ?string $parent_class,
        bool $evaluate_class_constants = true,
        bool $evaluate_conditional_types = false,
        bool $final = false,
        bool $expand_generic = false,
        bool $expand_templates = false,
        bool $throw_on_unresolvable_constant = false,
        array $expanded_parents = [],
    ): array {
        if (Type::isIntersectionType($return_type)) {
            /** @var TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject $return_type */
            if (count($return_type->getIntersectionTypes()) > 0) {
                throw new RecursivePropertiesOfIntersectionException(
                    'Intersections are not allowed in recursive-properties-of param (' . (string)$return_type . ').',
                );
            }
        }

        if ($return_type instanceof TArray) {
            return [self::expandArray(
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
                $expanded_parents,
            )];
        }

        if ($return_type instanceof TKeyedArray) {
            return [self::expandKeyedArray(
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
                $expanded_parents,
            )];
        }

        if ($return_type instanceof TIterable) {
            return [new TArray([Type::getArrayKey(), Type::getMixed()])];
        }

        if ($return_type instanceof TNamedObject) {
            return [self::expandNamedObject(
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
                $expanded_parents,
            )];
        }

        if ($return_type instanceof TObject) {
            if ($return_type instanceof TObjectWithProperties) {
                return [self::expandObjectWithProperties(
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
                    $expanded_parents,
                )];
            }

            return [new TArray([Type::getNever(), Type::getNever()])];
        }

        if ($return_type instanceof TRecursivePropertiesOf) {
            return array_values(self::expandUnion(
                $codebase,
                $return_type->types,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate_class_constants,
                $evaluate_conditional_types,
                $final,
                $expand_generic,
                $expand_templates,
                $throw_on_unresolvable_constant,
                $expanded_parents,
            )->getAtomicTypes());
        }

        if ($return_type instanceof TTemplateParam) {
            return [new TRecursivePropertiesOf(new Union([$return_type]))];
        }

        return [$return_type];
    }

    /**
     * Expand `recursive-properties-of<T>`, where `T` is an array type.
     *
     * Array types expand by applying `recursive-properties-of<T>` to their
     * value parameters. E.g. `recursive-properties-of<array<K, V>>` expands to
     * `array<K, recursive-properties-of<V>>`.
     *
     * @param list<TNamedObject> $expanded_parents
     */
    private static function expandArray(
        Codebase $codebase,
        TArray $return_type,
        ?string $self_class,
        string|TNamedObject|TTemplateParam|null $static_class_type,
        ?string $parent_class,
        bool $evaluate_class_constants = true,
        bool $evaluate_conditional_types = false,
        bool $final = false,
        bool $expand_generic = false,
        bool $expand_templates = false,
        bool $throw_on_unresolvable_constant = false,
        array $expanded_parents = [],
    ): TArray {
        return new TArray(
            [
                $return_type->type_params[0],
                self::expandUnion(
                    $codebase,
                    $return_type->type_params[1],
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates,
                    $throw_on_unresolvable_constant,
                    $expanded_parents,
                ),
            ],
        );
    }

    /**
     * Expand `recursive-properties-of<T>`, where `T` is an array shape type.
     *
     * Array shape types expand by applying `recursive-properties-of<T>` to
     * their value parameters. E.g. `recursive-properties-of<array{x: T}>`
     * expands to `array{x: recursive-properties-of<T>}`.
     *
     * @param list<TNamedObject> $expanded_parents
     */
    private static function expandKeyedArray(
        Codebase $codebase,
        TKeyedArray $return_type,
        ?string $self_class,
        string|TNamedObject|TTemplateParam|null $static_class_type,
        ?string $parent_class,
        bool $evaluate_class_constants = true,
        bool $evaluate_conditional_types = false,
        bool $final = false,
        bool $expand_generic = false,
        bool $expand_templates = false,
        bool $throw_on_unresolvable_constant = false,
        array $expanded_parents = [],
    ): TKeyedArray {
        $new_properties = [];

        foreach ($return_type->properties as $key => $value) {
            $new_properties[$key] = self::expandUnion(
                $codebase,
                $value,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate_class_constants,
                $evaluate_conditional_types,
                $final,
                $expand_generic,
                $expand_templates,
                $throw_on_unresolvable_constant,
                $expanded_parents,
            );
        }

        $new_fallback_params = null;

        if ($return_type->fallback_params !== null) {
            $new_fallback_params = [
                $return_type->fallback_params[0],
                self::expandUnion(
                    $codebase,
                    $return_type->fallback_params[1],
                    $self_class,
                    $static_class_type,
                    $parent_class,
                    $evaluate_class_constants,
                    $evaluate_conditional_types,
                    $final,
                    $expand_generic,
                    $expand_templates,
                    $throw_on_unresolvable_constant,
                    $expanded_parents,
                ),
            ];
        }

        return new TKeyedArray(
            $new_properties,
            $return_type->class_strings,
            $new_fallback_params,
            $return_type->is_list,
        );
    }

    /**
     * Expand `recursive-properties-of<T>`, where `T` is a named object type.
     *
     * Named object types expand into `recursive-properties-of<properties-of<T>>`.
     *
     * @param list<TNamedObject> $expanded_parents
     */
    private static function expandNamedObject(
        Codebase $codebase,
        TNamedObject $return_type,
        ?string $self_class,
        string|TNamedObject|TTemplateParam|null $static_class_type,
        ?string $parent_class,
        bool $evaluate_class_constants = true,
        bool $evaluate_conditional_types = false,
        bool $final = false,
        bool $expand_generic = false,
        bool $expand_templates = false,
        bool $throw_on_unresolvable_constant = false,
        array $expanded_parents = [],
    ): TKeyedArray|TPropertiesOf {
        if (($cycle_start = array_search($return_type, $expanded_parents)) !== false) {
            $cycle_path = implode(' -> ', array_slice($expanded_parents, $cycle_start)) . ' -> ' . (string)$return_type;

            throw new RecursivePropertiesOfCycleException(
                'Cyclic types are not allowed in recursive-properties-of param (' . $cycle_path . ').',
            );
        }

        $properties_of = new TPropertiesOf($return_type, null);

        $expanded_type = TypeExpander::expandAtomic(
            $codebase,
            $properties_of,
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

        assert(
            count($expanded_type) === 1 &&
                ($expanded_type[0] instanceof TKeyedArray || $expanded_type[0] instanceof TPropertiesOf),
            'Expanding TPropertiesOf should return either one TKeyedArray on success or one TPropertiesOf on failure'
                . ' to find the requested class.',
        );

        $expanded_type = $expanded_type[0];

        if ($expanded_type instanceof TKeyedArray) {
            $expanded_parents[] = $return_type;

            $expanded_type = self::expandKeyedArray(
                $codebase,
                $expanded_type,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate_class_constants,
                $evaluate_conditional_types,
                $final,
                $expand_generic,
                $expand_templates,
                $throw_on_unresolvable_constant,
                $expanded_parents,
            );

            array_pop($expanded_parents);
        }

        return $expanded_type;
    }

    /**
     * Expand `recursive-properties-of<T>`, where `T` is an object type with
     * properties.
     *
     * Object types with properties expand into array shapes, applying
     * `recursive-properties-of<T>` to their value parameters. E.g.
     * `recursive-properties-of<object{x: T}>` expands to
     * `array{x: recursive-properties-of<T>}`.
     *
     * @param list<TNamedObject> $expanded_parents
     */
    private static function expandObjectWithProperties(
        Codebase $codebase,
        TObjectWithProperties $return_type,
        ?string $self_class,
        string|TNamedObject|TTemplateParam|null $static_class_type,
        ?string $parent_class,
        bool $evaluate_class_constants = true,
        bool $evaluate_conditional_types = false,
        bool $final = false,
        bool $expand_generic = false,
        bool $expand_templates = false,
        bool $throw_on_unresolvable_constant = false,
        array $expanded_parents = [],
    ): TArray|TKeyedArray {
        $new_properties = [];

        foreach ($return_type->properties as $key => $value) {
            $new_properties[$key] = self::expandUnion(
                $codebase,
                $value,
                $self_class,
                $static_class_type,
                $parent_class,
                $evaluate_class_constants,
                $evaluate_conditional_types,
                $final,
                $expand_generic,
                $expand_templates,
                $throw_on_unresolvable_constant,
                $expanded_parents,
            );
        }

        if (count($new_properties) === 0) {
            return new TArray([Type::getNever(), Type::getNever()]);
        }

        return new TKeyedArray(
            $new_properties,
        );
    }
}
