<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNonspecificLiteralInt;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

use function count;
use function implode;

/**
 * The stub of implode() only sees what all the pieces have in common: an array shape whose pieces include a
 * non-empty one is joined into a non-empty string too, and so are two pieces with a non-empty separator.
 * A non-falsy piece, or a non-falsy separator between two pieces, makes it a non-falsy string
 *
 * @internal
 */
final class ImplodeReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['implode', 'join'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer || count($call_args) !== 2) {
            return null;
        }

        foreach ($call_args as $call_arg) {
            if ($call_arg->name !== null || $call_arg->unpack) {
                return null;
            }
        }

        $separator_type = $statements_source->node_data->getType($call_args[0]->value);
        $array_type = $statements_source->node_data->getType($call_args[1]->value);
        if ($separator_type === null || $array_type === null) {
            return null;
        }

        $separator_is_literal = true;
        $separator_is_non_empty = true;
        $separator_is_non_falsy = true;
        foreach ($separator_type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type instanceof TString) {
                return null;
            }

            $separator_is_literal = $separator_is_literal && self::isLiteral($atomic_type);
            $separator_is_non_empty = $separator_is_non_empty && self::isNonEmptyAsString($atomic_type);
            $separator_is_non_falsy = $separator_is_non_falsy && self::isNonFalsyAsString($atomic_type);
        }

        $pieces_are_literal = true;
        $is_non_falsy = true;
        foreach ($array_type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type instanceof TKeyedArray) {
                return null;
            }

            $has_non_empty_piece = false;
            $has_non_falsy_piece = false;
            $piece_count = 0;
            foreach ($atomic_type->properties as $property) {
                if (!$property->possibly_undefined) {
                    $piece_count++;
                    $has_non_empty_piece = $has_non_empty_piece || self::allNonEmptyAsString($property);
                    $has_non_falsy_piece = $has_non_falsy_piece || self::allNonFalsyAsString($property);
                }

                $pieces_are_literal = $pieces_are_literal && self::allLiteral($property);
            }

            if ($atomic_type->fallback_params !== null) {
                $pieces_are_literal = $pieces_are_literal && self::allLiteral($atomic_type->fallback_params[1]);
            }

            if (!$has_non_empty_piece && !($separator_is_non_empty && $piece_count > 1)) {
                return null;
            }

            $is_non_falsy = $is_non_falsy && ($has_non_falsy_piece || ($separator_is_non_falsy && $piece_count > 1));
        }

        if ($separator_is_literal && $pieces_are_literal) {
            // a non-falsy result of known literals is that literal: a literal string is not a non-falsy one otherwise
            $joined = $is_non_falsy ? self::joinLiterals($separator_type, $array_type) : null;

            return new Union([
                $joined !== null
                    ? Type::getAtomicStringFromLiteral($joined)
                    : new TNonEmptyNonspecificLiteralString(),
            ]);
        }

        return $is_non_falsy ? Type::getNonFalsyString() : Type::getNonEmptyString();
    }

    /**
     * @psalm-pure
     */
    private static function allNonEmptyAsString(Union $type): bool
    {
        foreach ($type->getAtomicTypes() as $atomic_type) {
            if (!self::isNonEmptyAsString($atomic_type)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The value of the call when the separator and every piece are known literals
     *
     * @psalm-pure
     */
    private static function joinLiterals(Union $separator_type, Union $array_type): ?string
    {
        if (!$separator_type->isSingleStringLiteral() || !$array_type->isSingle()) {
            return null;
        }

        $array_atomic_type = $array_type->getSingleAtomic();

        if (!$array_atomic_type instanceof TKeyedArray || $array_atomic_type->fallback_params !== null) {
            return null;
        }

        $pieces = [];

        foreach ($array_atomic_type->properties as $property) {
            if ($property->possibly_undefined || !$property->isSingle()) {
                return null;
            }

            $piece = $property->getSingleAtomic();

            if ($piece instanceof TLiteralString) {
                $pieces[] = $piece->value;
            } elseif ($piece instanceof TLiteralInt) {
                $pieces[] = (string) $piece->value;
            } else {
                return null;
            }
        }

        return implode($separator_type->getSingleStringLiteral()->value, $pieces);
    }

    /**
     * @psalm-pure
     */
    private static function allNonFalsyAsString(Union $type): bool
    {
        foreach ($type->getAtomicTypes() as $atomic_type) {
            if (!self::isNonFalsyAsString($atomic_type)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-pure
     */
    private static function isNonFalsyAsString(Atomic $type): bool
    {
        return $type instanceof TLiteralString
            ? $type->value !== '' && $type->value !== '0'
            : $type instanceof TNonFalsyString
                || $type instanceof TTrue
                || ($type instanceof TLiteralInt && $type->value !== 0);
    }

    /**
     * @psalm-pure
     */
    private static function isNonEmptyAsString(Atomic $type): bool
    {
        return $type instanceof TLiteralString
            ? $type->value !== ''
            : $type instanceof TNonEmptyString
                || $type instanceof TNonEmptyNonspecificLiteralString
                || $type instanceof TInt
                || $type instanceof TFloat
                || $type instanceof TTrue;
    }

    /**
     * @psalm-pure
     */
    private static function allLiteral(Union $type): bool
    {
        foreach ($type->getAtomicTypes() as $atomic_type) {
            if (!self::isLiteral($atomic_type)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-pure
     */
    private static function isLiteral(Atomic $type): bool
    {
        return $type instanceof TLiteralString
            || $type instanceof TNonspecificLiteralString
            || $type instanceof TLiteralInt
            || $type instanceof TNonspecificLiteralInt;
    }
}
