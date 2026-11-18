<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TDecimalIntString;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseNumericString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyLowercaseNumericString;
use Psalm\Type\Atomic\TNonFalsyLowercaseString;
use Psalm\Type\Atomic\TNonFalsyNumericString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTraitString;

use function is_numeric;
use function strtolower;

/**
 * The refinements of `string` that the string types are made of: a string type stands for the strings having all of
 * its refinements, so that the intersection of two string types has the refinements of both, and their union the
 * refinements they share.
 *
 * @psalm-immutable
 * @internal
 */
final class StringRefinement
{
    public const NON_EMPTY = 1;
    public const NON_FALSY = 2;
    public const LOWERCASE = 4;
    public const NUMERIC = 8;
    public const LITERAL = 16;

    /**
     * The refinements of a string type, or null for a string type that is not made of refinements, such as a
     * `class-string` or a `callable-string`
     *
     * @psalm-pure
     */
    public static function get(TString $type): ?int
    {
        if ($type instanceof TLiteralString) {
            $value = $type->value;

            return self::LITERAL
                | ($value !== '' ? self::NON_EMPTY : 0)
                | ($value !== '' && $value !== '0' ? self::NON_FALSY : 0)
                | (strtolower($value) === $value ? self::LOWERCASE : 0)
                | (is_numeric($value) ? self::NUMERIC : 0);
        }

        return match ($type::class) {
            TString::class => 0,
            TNonEmptyString::class => self::NON_EMPTY,
            TNonFalsyString::class => self::NON_EMPTY | self::NON_FALSY,
            TLowercaseString::class => self::LOWERCASE,
            TNonEmptyLowercaseString::class => self::NON_EMPTY | self::LOWERCASE,
            TNonFalsyLowercaseString::class => self::NON_EMPTY | self::NON_FALSY | self::LOWERCASE,
            TNumericString::class => self::NON_EMPTY | self::NUMERIC,
            TNonFalsyNumericString::class => self::NON_EMPTY | self::NON_FALSY | self::NUMERIC,
            TLowercaseNumericString::class => self::NON_EMPTY | self::LOWERCASE | self::NUMERIC,
            TNonFalsyLowercaseNumericString::class => self::NON_EMPTY | self::NON_FALSY | self::LOWERCASE
                | self::NUMERIC,
            TNonspecificLiteralString::class => self::LITERAL,
            TNonEmptyNonspecificLiteralString::class => self::NON_EMPTY | self::LITERAL,
            default => null,
        };
    }

    /**
     * The non-literal string type of the strings having the given refinements, or null when Psalm has no such type
     *
     * @psalm-pure
     */
    public static function getType(int $refinements, bool $from_docblock = false): ?TString
    {
        // a non-falsy or numeric string is non-empty
        if ($refinements & (self::NON_FALSY | self::NUMERIC)) {
            $refinements |= self::NON_EMPTY;
        }

        return match ($refinements) {
            0 => new TString($from_docblock),
            self::NON_EMPTY => new TNonEmptyString($from_docblock),
            self::NON_EMPTY | self::NON_FALSY => new TNonFalsyString($from_docblock),
            self::LOWERCASE => new TLowercaseString($from_docblock),
            self::NON_EMPTY | self::LOWERCASE => new TNonEmptyLowercaseString($from_docblock),
            self::NON_EMPTY | self::NON_FALSY | self::LOWERCASE => new TNonFalsyLowercaseString($from_docblock),
            self::NON_EMPTY | self::NUMERIC => new TNumericString($from_docblock),
            self::NON_EMPTY | self::NON_FALSY | self::NUMERIC => new TNonFalsyNumericString($from_docblock),
            self::NON_EMPTY | self::LOWERCASE | self::NUMERIC => new TLowercaseNumericString($from_docblock),
            self::NON_EMPTY | self::NON_FALSY | self::LOWERCASE | self::NUMERIC
                => new TNonFalsyLowercaseNumericString($from_docblock),
            self::LITERAL => new TNonspecificLiteralString($from_docblock),
            self::NON_EMPTY | self::LITERAL => new TNonEmptyNonspecificLiteralString($from_docblock),
            default => null,
        };
    }

    /**
     * The non-literal string type of the strings having both types, or null when either is a literal string or not
     * made of refinements, or when Psalm has no type for the intersection
     *
     * @psalm-pure
     */
    public static function intersect(TString $type_1, TString $type_2, bool $from_docblock = false): ?TString
    {
        if ($type_1 instanceof TLiteralString || $type_2 instanceof TLiteralString) {
            return null;
        }

        $refinements_1 = self::get($type_1);
        $refinements_2 = self::get($type_2);

        if ($refinements_1 === null || $refinements_2 === null) {
            return null;
        }

        return self::getType($refinements_1 | $refinements_2, $from_docblock);
    }

    /**
     * The string type that a string of one type being asserted to be of the other has, where neither contains the
     * other: the intersection of the refinements, or for the name of a class, a callable or a trait (which can be
     * lowercase and a literal, but never numeric) and a refined string, the name string, as Psalm has no type
     * for the intersection of the two
     *
     * @psalm-pure
     */
    public static function intersectAsserted(Atomic $type_1, Atomic $type_2): ?TString
    {
        if (!$type_1 instanceof TString || !$type_2 instanceof TString) {
            return null;
        }

        $refined = self::intersect($type_1, $type_2);

        if ($refined !== null) {
            return $refined;
        }

        if (self::isNameString($type_2)) {
            [$type_1, $type_2] = [$type_2, $type_1];
        }

        if (!self::isNameString($type_1) || $type_2 instanceof TLiteralString) {
            return null;
        }

        $refinements = self::get($type_2);

        return $refinements !== null
            && ($refinements & (self::LOWERCASE | self::LITERAL)) !== 0
            && ($refinements & self::NUMERIC) === 0
                ? $type_1
                : null;
    }

    /**
     * @psalm-pure
     */
    private static function isNameString(TString $type): bool
    {
        return $type instanceof TCallableString || $type instanceof TClassString || $type instanceof TTraitString;
    }

    /**
     * Whether a string type is one of those only the refinements model, which the rest of Psalm compares and
     * combines through them
     *
     * @psalm-pure
     */
    public static function isCombinedRefinement(TString $type): bool
    {
        return $type instanceof TNonFalsyLowercaseString
            || $type instanceof TNonFalsyNumericString
            || $type instanceof TLowercaseNumericString;
    }

    /**
     * The refinements every string of a string type has, for a type not made of refinements
     *
     * @psalm-pure
     */
    public static function getImplied(TString $type): int
    {
        return self::get($type) ?? match (true) {
            // "0", "123" or "-5"
            $type instanceof TDecimalIntString => self::NON_EMPTY | self::LOWERCASE | self::NUMERIC,
            $type instanceof TNumericString => self::NON_EMPTY | self::NUMERIC,
            $type instanceof TNonFalsyString => self::NON_EMPTY | self::NON_FALSY,
            $type instanceof TNonEmptyString => self::NON_EMPTY,
            default => 0,
        };
    }
}
