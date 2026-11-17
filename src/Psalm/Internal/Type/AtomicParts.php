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
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TEmptyNumeric;
use Psalm\Type\Atomic\TEmptyScalar;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyScalar;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;

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
     * The parts of a type to intersect with another type
     *
     * @return non-empty-list<Atomic>|null null for a type that is not made of several kinds, or where the comparators
     *     already find the overlap: the Traversable half of an iterable compared with a class, the classes and the
     *     function names a callable may be
     */
    public static function split(Atomic $type, Atomic $other, Codebase $codebase): ?array
    {
        $from_docblock = $type->from_docblock;

        if ($type instanceof TIterable) {
            return $other instanceof TNamedObject ? null : self::splitIterable($type, $codebase);
        }

        if ($type::class === TCallable::class) {
            if ($other instanceof TLiteralString) {
                return null;
            }

            $closure = new TClosure($type->params, $type->return_type, $type->purity, [], [], $from_docblock);

            if ($other instanceof TNamedObject) {
                // a Closure of the signature of the callable
                return $other->value === 'Closure' ? [$closure] : null;
            }

            return [
                new TCallableString($from_docblock),
                Atomic::create('callable-array', null, [], [], null, null, null, $from_docblock),
                new TCallableObject(
                    $from_docblock,
                    $type->params !== null || $type->return_type !== null ? $type : null,
                ),
            ];
        }

        return match ($type::class) {
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
            TNonEmptyScalar::class => [
                new TInt($from_docblock),
                new TFloat($from_docblock),
                new TNonFalsyString($from_docblock),
                new TTrue($from_docblock),
            ],
            TEmptyScalar::class => [
                new TLiteralInt(0, $from_docblock),
                new TLiteralFloat(0.0, $from_docblock),
                TLiteralString::make('', $from_docblock),
                TLiteralString::make('0', $from_docblock),
                new TFalse($from_docblock),
            ],
            TEmptyNumeric::class => [
                new TLiteralInt(0, $from_docblock),
                new TLiteralFloat(0.0, $from_docblock),
                TLiteralString::make('0', $from_docblock),
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
        // the purity of the iterable is the last template of Traversable
        $traversable = $type->has_docblock_params || $type->purity->getId() !== Type::getImpure()->getId()
            ? new TGenericObject(
                'Traversable',
                [...$type->type_params, $type->purity],
                false,
                false,
                $type->extra_types,
                $from_docblock,
            )
            : new TNamedObject('Traversable', false, false, $type->extra_types, $from_docblock);

        $array_key = Type::intersectUnionTypes($type->type_params[0], Type::getArrayKey(), $codebase);

        // an iterable intersected with another type is an object
        if ($type->extra_types || $array_key === null || $array_key->isNever()) {
            return [$traversable];
        }

        return [new TArray([$array_key, $type->type_params[1]], $from_docblock), $traversable];
    }
}
