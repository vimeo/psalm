<?php

declare(strict_types=1);

namespace Psalm\Internal\Overlay;

use Psalm\Config;
use Psalm\Internal\BaselineMatcher;

use function array_keys;
use function array_slice;
use function array_unique;
use function count;
use function min;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function preg_replace_callback;
use function str_contains;
use function str_replace;
use function strlen;
use function strrev;
use function strspn;
use function strtolower;
use function substr;
use function usort;

/**
 * Which issues of an analysis with overlays the overlays introduce, given the issues of the same analysis without
 * them. Both lists are issues as the JSON report has them, after issueHandlers and `@psalm-suppress`, before the
 * baseline.
 *
 * An issue is identified by its file, its type and its message with the positions it quotes left out. An identity
 * that the analysis with the overlays has N more times introduces N issues: a line shift, or an edit inside code
 * that already had the issue, introduces nothing. Positions only choose which occurrences are the introduced ones.
 *
 * @psalm-type IssueArray = array{
 *     severity: string,
 *     type: string,
 *     message: string,
 *     file_name: string,
 *     file_path: string,
 *     selected_text: string,
 *     from: int,
 *     line_from: int,
 *     ...
 * }
 * @psalm-type GeneratedNames = array{array<string, true>, list<string>}
 * @internal
 * @psalm-capabilities read-props|write-this-props|write-refs
 */
final class IssueDiff
{
    /** They report that an issue went away */
    public const NEVER_INTRODUCED = [
        'UnusedPsalmSuppress' => true,
        'UnusedBaselineEntry' => true,
        'UnusedIssueHandlerSuppression' => true,
    ];

    /**
     * The introduced issues, with the severity the baseline leaves them. The baseline is used up by the issues
     * that were there before first, so that only an entry no such issue takes can cover an introduced one.
     *
     * @param list<IssueArray> $base
     * @param list<IssueArray> $overlay
     * @param array<string, array{string, string}> $contents path of an overlaid file => [contents, overlay]
     * @param array<string, array<string, array{o: int, s: array<int, string>}>> $baseline
     * @param Config $config the types it lets the baseline cover
     * @return list<IssueArray>
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public static function getNewIssues(
        array $base,
        array $overlay,
        array $contents,
        array $baseline,
        Config $config,
    ): array {
        $file_paths = array_keys($contents);
        foreach ([...$base, ...$overlay] as $issue) {
            $file_paths[] = $issue['file_path'];
        }
        $generated = self::getGeneratedNames(array_unique($file_paths));

        $base_by_identity = [];
        foreach ($base as $issue) {
            if (!isset(self::NEVER_INTRODUCED[$issue['type']])) {
                $base_by_identity[self::getIdentity($issue, $generated)][] = $issue;
            }
        }

        $kept = [];
        foreach ($overlay as $issue) {
            if (!isset(self::NEVER_INTRODUCED[$issue['type']])) {
                $kept[] = $issue;
            }
        }
        $overlay = $kept;

        $overlay_by_identity = [];
        foreach ($overlay as $i => $issue) {
            $overlay_by_identity[self::getIdentity($issue, $generated)][] = $i;
        }

        $windows = [];
        foreach ($contents as $file_path => [$old, $new]) {
            $windows[$file_path] = self::getEditWindow($old, $new);
        }

        $introduced = [];
        foreach ($overlay_by_identity as $identity => $indexes) {
            $base_occurrences = $base_by_identity[$identity] ?? [];

            // where each occurrence is expected with the overlays, unless the edit rewrote its code
            $expected = [];
            foreach ($base_occurrences as $issue) {
                $offset = self::mapOffset($issue['from'], $windows[$issue['file_path']] ?? null);
                if ($offset !== null) {
                    $expected[$offset] = ($expected[$offset] ?? 0) + 1;
                }
            }

            $unaligned = [];
            $aligned = 0;
            foreach ($indexes as $i) {
                $from = $overlay[$i]['from'];
                if (($expected[$from] ?? 0) > 0) {
                    $expected[$from]--;
                    $aligned++;
                } else {
                    $unaligned[] = $i;
                }
            }

            // the occurrences the edit moved or rewrote are the first ones that are not where they were
            $moved = min(count($base_occurrences) - $aligned, count($unaligned));
            foreach (array_slice($unaligned, $moved) as $i) {
                $introduced[$i] = true;
            }
        }

        $order = array_keys($overlay);
        usort(
            $order,
            static fn(int $a, int $b): int => [
                isset($introduced[$a]),
                $overlay[$a]['file_path'],
                $overlay[$a]['from'],
                $a,
            ] <=> [
                isset($introduced[$b]),
                $overlay[$b]['file_path'],
                $overlay[$b]['from'],
                $b,
            ],
        );

        $new_issues = [];
        foreach ($order as $i) {
            $issue = $overlay[$i];
            $type = $issue['type'];

            if (BaselineMatcher::consume($config, $baseline, $issue['file_name'], $type, $issue['selected_text'])) {
                $issue['severity'] = 'info';
            }

            if (isset($introduced[$i])) {
                $new_issues[] = $issue;
            }
        }

        return $new_issues;
    }

    /**
     * The message without the positions some messages quote, which a line shift changes
     *
     * @param GeneratedNames $generated see getGeneratedNames()
     * @psalm-pure
     */
    public static function normalizeMessage(string $type, string $message, array $generated): string
    {
        if ($type === 'ParseError') {
            // the parser ends its messages with the line
            $message = (string) preg_replace('/ on (?:line \d+|unknown line)$/', ' on line #', $message);
        }

        if ($type === 'PossiblyUndefinedVariable' || $type === 'PossiblyUndefinedGlobalVariable') {
            // "Possibly undefined variable $a, first seen on line 12"
            $message = (string) preg_replace(
                '/^(Possibly undefined (?:global )?variable \S+, first seen on line )\d+$/',
                '${1}#',
                $message,
            );
        }

        return self::normalizeGeneratedNames($message, $generated);
    }

    /**
     * What the names Psalm gives the closures and anonymous classes of these files start with: they continue with
     * the position of what they name
     *
     * @param array<string> $file_paths
     * @return GeneratedNames
     * @psalm-pure
     */
    public static function getGeneratedNames(array $file_paths): array
    {
        $anonymous_classes = [];
        $closures = [];
        foreach ($file_paths as $file_path) {
            // <file, with _ for every other character>_<line>_<offset>, in the case a message may give it
            $anonymous_classes[strtolower((string) preg_replace('/[^A-Za-z0-9]/', '_', $file_path))] = true;
            // <lowercase file>:<line>:<offset>:-:closure
            $closures[strtolower($file_path)] = true;
        }

        return [$anonymous_classes, array_keys($closures)];
    }

    /**
     * @param GeneratedNames $generated
     * @psalm-pure
     */
    public static function normalizeGeneratedNames(string $value, array $generated): string
    {
        [$anonymous_classes, $closures] = $generated;

        if ($anonymous_classes && preg_match('/_\d+_\d+/', $value)) {
            $value = (string) preg_replace_callback(
                '/(?<![A-Za-z0-9_])([A-Za-z0-9_]+)_\d+_\d+(?![A-Za-z0-9_])/',
                static function (array $match) use ($anonymous_classes): string {
                    $name = $match[1] ?? '';

                    return isset($anonymous_classes[strtolower($name)]) ? $name . '_#_#' : ($match[0] ?? '');
                },
                $value,
            );
        }

        if (str_contains($value, ':-:closure')) {
            foreach ($closures as $closure) {
                if (str_contains($value, $closure)) {
                    $value = (string) preg_replace(
                        '/' . preg_quote($closure, '/') . ':\d+:\d+:-:closure/',
                        $closure . ':#:#:-:closure',
                        $value,
                    );
                }
            }
        }

        return $value;
    }

    /**
     * @param IssueArray $issue
     * @param GeneratedNames $generated
     * @psalm-pure
     */
    private static function getIdentity(array $issue, array $generated): string
    {
        return str_replace('\\', '/', $issue['file_name'])
            . "\0" . $issue['type']
            . "\0" . self::normalizeMessage($issue['type'], $issue['message'], $generated);
    }

    /**
     * The offset with the overlay of an offset without it, null inside the edited part. A file that is not
     * overlaid keeps its offsets.
     *
     * @param array{int, int, int}|null $window see getEditWindow()
     * @psalm-pure
     */
    private static function mapOffset(int $offset, ?array $window): ?int
    {
        if ($window === null) {
            return $offset;
        }

        [$prefix, $suffix_start, $shift] = $window;

        if ($offset < $prefix) {
            return $offset;
        }

        if ($offset >= $suffix_start) {
            return $offset + $shift;
        }

        return null;
    }

    /**
     * The edited part is everything between the common prefix and the common suffix
     *
     * @return array{int, int, int} the length of the common prefix, the offset of the common suffix without the
     *      overlay, and how far the overlay shifts the suffix
     * @psalm-pure
     */
    private static function getEditWindow(string $old, string $new): array
    {
        $old_length = strlen($old);
        $new_length = strlen($new);
        $max = min($old_length, $new_length);

        $prefix = strspn(substr($old, 0, $max) ^ substr($new, 0, $max), "\0");
        $tail = $max - $prefix;
        $suffix = $tail > 0
            ? strspn(strrev(substr($old, $old_length - $tail)) ^ strrev(substr($new, $new_length - $tail)), "\0")
            : 0;

        return [$prefix, $old_length - $suffix, $new_length - $old_length];
    }
}
