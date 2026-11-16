<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TTemplateParam;

use function array_diff_key;
use function assert;

/**
 * An intersection of an object shape and a class is represented with the class first (`Foo&object{a: int}`):
 * the analyzers of methods, properties and constants look at the primary type of an intersection and find
 * everything a class declares there, whereas an object shape knows nothing of it.
 *
 * @internal
 */
final class ObjectShapeIntersection
{
    /**
     * Intersects the types in their order, except that a class or template takes the place of a leading object
     * shape (`object{a: int}&Foo` is `Foo&object{a: int}`), and that all object shapes merge into one, the last
     * type: intersection types are keyed by `getKey()`, which is `object` for every shape.
     *
     * @param non-empty-list<TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $types
     * @psalm-pure
     */
    public static function intersect(
        array $types,
    ): TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject {
        $shape = null;
        $other_types = [];

        foreach ($types as $type) {
            foreach ([$type->setIntersectionTypes([]), ...$type->getIntersectionTypes()] as $part) {
                if ($part instanceof TObjectWithProperties) {
                    $shape = $shape === null ? $part : self::mergeShapes($shape, $part);
                } else {
                    $other_types[] = $part;
                }
            }
        }

        $primary = null;

        foreach ($other_types as $other_type) {
            if (!$types[0] instanceof TObjectWithProperties
                || $other_type instanceof TNamedObject
                || $other_type instanceof TTemplateParam
            ) {
                $primary = $other_type;
                break;
            }
        }

        if ($primary === null) {
            assert($shape !== null);
            $primary = $shape;
            $shape = null;
        }

        $intersection_types = [];

        foreach ($other_types as $other_type) {
            if ($other_type->getId() === $primary->getId()) {
                continue;
            }

            // `iterable<mixed, A>&iterable<mixed, B>` share their key
            $key = $other_type instanceof TIterable ? $other_type->getId() : $other_type->getKey();
            $intersection_types[$key] = $other_type;
        }

        if ($shape !== null) {
            $intersection_types[$shape->getKey()] = $shape;
        }

        return $primary->setIntersectionTypes($intersection_types);
    }

    /**
     * The object shape among the parts of an intersection (`object{a: int}` of `Foo&object{a: int}`)
     *
     * @psalm-pure
     */
    public static function getShape(Atomic $type): ?TObjectWithProperties
    {
        if ($type instanceof TObjectWithProperties) {
            return $type;
        }

        if ($type instanceof TNamedObject
            || $type instanceof TTemplateParam
            || $type instanceof TIterable
            || $type instanceof TCallableObject
        ) {
            foreach ($type->extra_types as $extra_type) {
                if ($extra_type instanceof TObjectWithProperties) {
                    return $extra_type;
                }
            }
        }

        return null;
    }

    /**
     * A property both shapes declare gets one of the two types, either of which is true of an object of both
     * shapes: the narrower one where the atomic types of one are among those of the other (`int` of `int|string`),
     * as computing the actual intersection of the types takes a codebase
     *
     * @psalm-pure
     */
    private static function mergeShapes(
        TObjectWithProperties $shape,
        TObjectWithProperties $other_shape,
    ): TObjectWithProperties {
        $properties = $shape->properties;

        foreach ($other_shape->properties as $name => $type) {
            if (!isset($properties[$name])) {
                $properties[$name] = $type;
                continue;
            }

            $existing_type = $properties[$name];

            if (array_diff_key($type->getAtomicTypes(), $existing_type->getAtomicTypes()) !== []) {
                $type = $existing_type;
            }

            $properties[$name] = $type->setPossiblyUndefined(
                $existing_type->possibly_undefined && $other_shape->properties[$name]->possibly_undefined,
            );
        }

        return $shape
            ->setProperties($properties)
            ->setMethods($shape->methods + $other_shape->methods)
            ->setNarrowedFrom($shape->narrowed_from + $other_shape->narrowed_from);
    }
}
