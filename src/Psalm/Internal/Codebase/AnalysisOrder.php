<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Closure;

use function uksort;

/**
 * Decides in which order files are handed to the analysis workers.
 *
 * Workers pull one file at a time from a shared queue, so the run ends when the last worker finishes. Starting the
 * most expensive files first (longest processing time first) keeps one slow file from being picked up at the
 * very end and defining the length of the whole phase.
 *
 * @psalm-pure
 * @internal
 */
final class AnalysisOrder
{
    /**
     * @param array<string, string> $files files to analyse, keyed by path
     * @param array<string, float> $timings seconds a previous run spent on a file
     * @param Closure[_](string): int $size_of cheap proxy for the cost of a file without history
     * @return array<string, string> the same files, most expensive first; ties are broken by path
     * @psalm-pure
     */
    public static function longestFirst(array $files, array $timings, Closure $size_of): array
    {
        $sizes = [];
        foreach ($files as $path => $_) {
            $sizes[$path] = $size_of($path);
        }

        // Express the size of files without history in seconds, using the observed cost per byte of the files
        // that have both, so that the two kinds of estimates can be compared.
        $known_seconds = 0.0;
        $known_bytes = 0;
        foreach ($sizes as $path => $size) {
            if (isset($timings[$path])) {
                $known_seconds += $timings[$path];
                $known_bytes += $size;
            }
        }

        $seconds_per_byte = $known_bytes > 0 && $known_seconds > 0.0 ? $known_seconds / (float) $known_bytes : 1.0;

        $costs = [];
        foreach ($sizes as $path => $size) {
            $costs[$path] = $timings[$path] ?? (float) $size * $seconds_per_byte;
        }

        $files_in_order = $files;
        uksort(
            $files_in_order,
            static fn(string $a, string $b): int => $costs[$b] <=> $costs[$a] ?: $a <=> $b,
        );

        return $files_in_order;
    }
}
