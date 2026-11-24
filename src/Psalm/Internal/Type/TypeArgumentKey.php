<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Union;

use function implode;
use function sort;

/**
 * The part a type argument takes in the key of a generic atomic (`Foo<int>`, `TConditional<value-of<T>>`): literals
 * read as their type, since TypeCombiner merges the generic objects that only differ by their type arguments
 *
 * @internal
 */
final class TypeArgumentKey
{
    /**
     * @psalm-pure
     */
    public static function get(Union $type): string
    {
        $types = [];

        $printed_int = false;
        $printed_float = false;
        $printed_string = false;

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TLiteralFloat) {
                if ($printed_float) {
                    continue;
                }

                $types[] = 'float';
                $printed_float = true;
            } elseif ($atomic_type instanceof TLiteralString) {
                if ($printed_string) {
                    continue;
                }

                $types[] = 'string';
                $printed_string = true;
            } elseif ($atomic_type instanceof TLiteralInt) {
                if ($printed_int) {
                    continue;
                }

                $types[] = 'int';
                $printed_int = true;
            } else {
                $types[] = $atomic_type->getKey();
            }
        }

        sort($types);

        return implode('|', $types);
    }
}
