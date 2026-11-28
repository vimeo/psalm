<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Type\Union;

/**
 * A comparison proving that a type contains a lower bound (`@template T super Cat`) whatever its templates stand for.
 * While one runs, the comparators read a template as containing only itself and what its own lower bound contains,
 * not what its upper bound allows, and leave out the coercions that are no subtyping: an int for a float, an object
 * with `__toString()` for a string, a literal string for a callable.
 *
 * @internal
 */
final class LowerBoundProof
{
    /**
     * How many proofs are running, one within another
     */
    private static int $depth = 0;

    public static function isContainedBy(Codebase $codebase, Union $input_type, Union $container_type): bool
    {
        self::$depth++;

        try {
            return UnionTypeComparator::isContainedBy(
                $codebase,
                $input_type,
                $container_type,
                false,
                false,
                null,
                false,
                false,
            );
        } finally {
            self::$depth--;
        }
    }

    /**
     * @psalm-capabilities read-globals
     */
    public static function isRunning(): bool
    {
        return self::$depth > 0;
    }
}
