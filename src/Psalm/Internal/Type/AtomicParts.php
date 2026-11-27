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
use Psalm\Type\Atomic\TIntRange;
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
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

use function array_values;

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

            if ($other instanceof TNamedObject) {
                // a Closure of the signature of the callable
                return $other->value === 'Closure'
                    ? [new TClosure($type->params, $type->return_type, $type->purity, [], [], $from_docblock)]
                    : null;
            }

            $string_and_array = [
                new TCallableString($from_docblock),
                Atomic::create('callable-array', null, [], [], null, null, null, $from_docblock),
            ];

            // as with a class, whether a Traversable may be invokable is up to the comparators
            return $other instanceof TIterable
                ? $string_and_array
                : [
                    ...$string_and_array,
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
            TEmptyNumeric::class => [
                new TLiteralInt(0, $from_docblock),
                new TLiteralFloat(0.0, $from_docblock),
                TLiteralString::make('0', $from_docblock),
            ],
            default => self::getScalarParts($type),
        };
    }

    /**
     * The parts of a scalar, of a non-empty-scalar (what is not falsy) and of an empty-scalar (what is falsy, or the
     * string '0')
     *
     * @return non-empty-list<Atomic>|null
     * @psalm-pure
     */
    public static function getScalarParts(Atomic $type): ?array
    {
        $from_docblock = $type->from_docblock;

        return match ($type::class) {
            TScalar::class => [
                new TInt($from_docblock),
                new TFloat($from_docblock),
                new TString($from_docblock),
                new TBool($from_docblock),
            ],
            TNonEmptyScalar::class => [
                new TIntRange(null, -1, $from_docblock),
                new TIntRange(1, null, $from_docblock),
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
            default => null,
        };
    }

    /**
     * The parts of a scalar that are of one kind, as a runtime check proves them (not docblock-only)
     *
     * @param class-string<TInt>|class-string<TFloat>|class-string<TString>|class-string<TBool> $kind
     * @return list<Atomic>
     * @psalm-pure
     */
    public static function getScalarPartsOfKind(TScalar $type, string $kind): array
    {
        $parts = [];

        foreach (self::getScalarParts($type) ?? [] as $part) {
            if ($part instanceof $kind) {
                $parts[] = $part->setFromDocblock(false);
            }
        }

        return $parts;
    }

    /**
     * The object half of an iterable, of its key and value types (or the given ones) and its purity
     *
     * @param array{Union, Union}|null $type_params
     * @psalm-pure
     */
    public static function getTraversable(TIterable $type, ?array $type_params = null): TNamedObject
    {
        $from_docblock = $type->from_docblock;
        $is_impure = $type->purity->getId() === Type::getImpure()->getId();

        if (!$type->has_docblock_params && $type_params === null && $is_impure) {
            return new TNamedObject('Traversable', false, false, $type->extra_types, $from_docblock);
        }

        $type_params ??= $type->type_params;

        // the purity of the iterable is the last template of Traversable
        return new TGenericObject(
            'Traversable',
            $is_impure ? $type_params : [...$type_params, $type->purity],
            false,
            false,
            $type->extra_types,
            $from_docblock,
        );
    }

    /**
     * @return non-empty-list<Atomic>
     */
    private static function splitIterable(TIterable $type, Codebase $codebase): array
    {
        $from_docblock = $type->from_docblock;
        $traversable = self::getTraversable($type);

        // an iterable intersected with another type is an object
        if ($type->extra_types) {
            return [$traversable];
        }

        $array_key = self::getArrayKeys($type->type_params[0], $codebase);

        // an iterable whose keys can't be array keys may still be the empty array
        if ($array_key === null) {
            return [Type::getEmptyArrayAtomic(), $traversable];
        }

        return [new TArray([$array_key, $type->type_params[1]], $from_docblock), $traversable];
    }

    /**
     * The keys of a type that can be array keys, a template keeping its name, or null if there are none
     */
    private static function getArrayKeys(Union $key, Codebase $codebase): ?Union
    {
        $array_keys = [];

        foreach ($key->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TTemplateParam) {
                $as = self::getArrayKeys($atomic->as, $codebase);

                if ($as !== null) {
                    $array_keys[] = $atomic->replaceAs($as);
                }

                continue;
            }

            $atomic_array_keys = Type::intersectUnionTypes(new Union([$atomic]), Type::getArrayKey(), $codebase);

            if ($atomic_array_keys !== null && !$atomic_array_keys->isNever()) {
                $array_keys = [...$array_keys, ...array_values($atomic_array_keys->getAtomicTypes())];
            }
        }

        return $array_keys ? TypeCombiner::combine($array_keys) : null;
    }
}
