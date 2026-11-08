<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Closure;

use function array_slice;
use function uksort;

/**
 * Decides in which order files are handed to the analysis workers.
 *
 * Workers pull one file at a time from a shared queue, so the phase ends when the last worker finishes. A file that
 * takes much longer than the others and is picked up near the end defines the length of the whole phase, so the
 * files estimated to be the most expensive start first.
 *
 * @internal
 */
final class AnalysisOrder
{
    /**
     * @param array<string, string> $files files to analyse, keyed by path, in the order they are analysed otherwise
     * @param int $count how many files to move to the front
     * @param Closure[_](string): int $cost_of estimated cost of a file; files estimated at 0 are never moved
     * @return array<string, string> the $count most expensive files, most expensive first and ties broken by path,
     *                               then the rest in their original order
     * @psalm-pure
     */
    public static function mostExpensiveFirst(array $files, int $count, Closure $cost_of): array
    {
        if ($count <= 0) {
            return $files;
        }

        $costs = [];
        foreach ($files as $path => $_) {
            $cost = $cost_of($path);
            if ($cost > 0) {
                $costs[$path] = $cost;
            }
        }

        uksort(
            $costs,
            static fn(string $a, string $b): int => $costs[$b] <=> $costs[$a] ?: $a <=> $b,
        );

        $first = [];
        foreach (array_slice($costs, 0, $count, true) as $path => $_) {
            $first[$path] = $files[$path];
        }

        return $first + $files;
    }
}
