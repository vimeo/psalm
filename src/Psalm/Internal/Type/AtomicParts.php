<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;

/**
 * The disjoint types that a type standing for values of several kinds is made of, so that it can be intersected with
 * a type overlapping only one of them: `callable` and `string` share the `callable-string`, `iterable` and `object`
 * the `Traversable`, `numeric` and `string` the `numeric-string`.
 *
 * @internal
 */
final class AtomicParts
{
    /**
     * @return non-empty-list<Atomic>|null null for a type that is not made of several kinds
     */
    public static function split(Atomic $type, Codebase $codebase): ?array
    {
        $from_docblock = $type->from_docblock;

        if ($type instanceof TIterable) {
            return self::splitIterable($type, $codebase);
        }

        return match ($type::class) {
            TCallable::class => [
                new TCallableString($from_docblock),
                Atomic::create('callable-array', null, [], [], null, null, null, $from_docblock),
                new TCallableObject(
                    $from_docblock,
                    $type->params !== null || $type->return_type !== null ? $type : null,
                ),
            ],
            TNumeric::class => [
                new TInt($from_docblock),
                new TFloat($from_docblock),
                new TNumericString($from_docblock),
            ],
            TArrayKey::class => [new TInt($from_docblock), new TString($from_docblock)],
            TScalar::class => [
                new TInt($from_docblock),
                new TFloat($from_docblock),
                new TString($from_docblock),
                new TBool($from_docblock),
            ],
            default => null,
        };
    }

    /**
     * @return non-empty-list<Atomic>
     */
    private static function splitIterable(TIterable $type, Codebase $codebase): array
    {
        $from_docblock = $type->from_docblock;
        $traversable = $type->has_docblock_params
            ? new TGenericObject('Traversable', $type->type_params, false, false, $type->extra_types, $from_docblock)
            : new TNamedObject('Traversable', false, false, $type->extra_types, $from_docblock);

        $array_key = Type::intersectUnionTypes($type->type_params[0], Type::getArrayKey(), $codebase);

        // an iterable intersected with another type is an object
        if ($type->extra_types || $array_key === null || $array_key->isNever()) {
            return [$traversable];
        }

        return [new TArray([$array_key, $type->type_params[1]], $from_docblock), $traversable];
    }
}
