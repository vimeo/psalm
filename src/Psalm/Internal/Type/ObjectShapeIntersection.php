<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TTemplateParam;

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
     * Writes `object{a: int}&Foo&Bar` as `Foo&Bar&object{a: int}`; any other type is returned unchanged
     *
     * @psalm-pure
     */
    public static function classFirst(Atomic $type): Atomic
    {
        if (!$type instanceof TObjectWithProperties) {
            return $type;
        }

        foreach ($type->extra_types as $key => $class) {
            if (!$class instanceof TNamedObject && !$class instanceof TTemplateParam) {
                continue;
            }

            $other_types = $type->extra_types;
            unset($other_types[$key]);
            $shape = $type->setIntersectionTypes([]);

            return $class->setIntersectionTypes([
                ...$class->getIntersectionTypes(),
                ...$other_types,
                $shape->getKey() => $shape,
            ]);
        }

        return $type;
    }
}
