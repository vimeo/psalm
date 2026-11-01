<?php

declare(strict_types=1);

/**
 * @param list<string> $args
 */
function runNormalize(array $args): int
{
    if (count($args) !== 3) {
        throw new RuntimeException('normalize takes <report.json> <project root> <out.issues.json>');
    }

    echo normalizeReport(...$args), "\n";

    return 0;
}

/**
 * Writes the deterministic issue list that compare diffs; returns the issue count.
 *
 * - paths are project-relative (also inside messages)
 * - digit runs of >= 9 chars become '#' (runtime values such as REQUEST_TIME differ per run)
 * - sorted; severity is part of an issue's identity, error_level (0 when missing) is not
 *
 * @throws RuntimeException when the report is unreadable or the output cannot be written
 */
function normalizeReport(string $reportFile, string $root, string $outFile): int
{
    ini_set('memory_limit', '-1');
    $report = readJsonFile($reportFile);
    if (!array_is_list($report)) {
        throw new RuntimeException("{$reportFile} is not a Psalm JSON report");
    }

    $roots = array_values(array_unique(array_filter([rtrim($root, '/'), (string) realpath($root)])));
    $issues = array_map(static fn(array $issue): array => normalizeIssue($issue, $roots), $report);
    unset($report);
    usort($issues, static fn(array $a, array $b): int => [$a['file'], $a['line_from'], $a['column_from'], $a['type'],
        $a['message'], $a['severity']] <=> [$b['file'], $b['line_from'], $b['column_from'], $b['type'], $b['message'],
        $b['severity']]);
    writeJsonFile($outFile, $issues, false);

    return count($issues);
}

/**
 * @param array<string, mixed> $issue
 * @param list<string> $roots
 * @return array{type: string, severity: string, file: string, line_from: int, column_from: int, message: string,
 *     error_level: int}
 */
function normalizeIssue(array $issue, array $roots): array
{
    $file = (string) ($issue['file_path'] ?? '');
    $message = (string) ($issue['message'] ?? '');
    $relative = null;
    foreach ($roots as $root) {
        if ($relative === null && str_starts_with($file, $root . '/')) {
            $relative = substr($file, strlen($root) + 1);
        }

        $message = str_replace($root . '/', '', $message);
    }

    return [
        'type' => (string) ($issue['type'] ?? '?'),
        'severity' => (string) ($issue['severity'] ?? ''),
        'file' => $relative ?? (string) ($issue['file_name'] ?? $file),
        'line_from' => (int) ($issue['line_from'] ?? 0),
        'column_from' => (int) ($issue['column_from'] ?? 0),
        'message' => (string) preg_replace('/\d{9,}/', '#', $message),
        'error_level' => (int) ($issue['error_level'] ?? 0),
    ];
}
