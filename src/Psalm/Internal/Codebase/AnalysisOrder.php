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
     * @param Closure[_](string): int $size_of size proxy for a file's cost; needed only if a file has no history
     * @return array<string, string> the same files, most expensive first; ties are broken by path
     * @psalm-pure
     */
    public static function longestFirst(array $files, array $timings, Closure $size_of): array
    {
        $costs = [];
        $without_history = [];
        foreach ($files as $path => $_) {
            if (isset($timings[$path])) {
                $costs[$path] = $timings[$path];
            } else {
                $without_history[] = $path;
            }
        }

        if ($without_history !== []) {
            // Express the size of files without history in seconds, using the observed cost per byte of the files
            // that have both, so that the two kinds of estimates can be compared.
            $known_seconds = 0.0;
            $known_bytes = 0;
            foreach ($costs as $path => $seconds) {
                $known_seconds += $seconds;
                $known_bytes += $size_of($path);
            }

            $seconds_per_byte = $known_bytes > 0 && $known_seconds > 0.0 ? $known_seconds / (float) $known_bytes : 1.0;

            foreach ($without_history as $path) {
                $costs[$path] = (float) $size_of($path) * $seconds_per_byte;
            }
        }

        $files_in_order = $files;
        uksort(
            $files_in_order,
            static fn(string $a, string $b): int => $costs[$b] <=> $costs[$a] ?: $a <=> $b,
        );

        return $files_in_order;
    }
}
