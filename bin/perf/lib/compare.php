<?php

declare(strict_types=1);

const MB_FAIL_PCT = 5.0;
const WALL_WARN_PCT = 20.0;
/**
 * Head timeout or memory crash fails only if base used under this share of the limit hit (else warn); any other
 * head crash fails. When the PR changes composer.json, MB and wall deltas cannot fail the check.
 */
const LIMIT_HEADROOM = 0.5;
const EXAMPLES_PER_LIST = 10;
const MESSAGE_CHARS = 300;
const STATUSES = ['ok', 'crash', 'timeout', 'error'];
/** 'skip' (➖, base not ok, not comparable) counts as warn overall. */
const VERDICT_RANK = ['pass' => 0, 'skip' => 1, 'warn' => 1, 'fail' => 2];

/**
 * @param array<mixed> $plan
 * @return array{verdict: string, deps_changed: bool, projects: array<string, array<string, mixed>>}
 */
function compareAll(array $plan, string $inDir, bool $depsChanged): array
{
    $projects = [];
    foreach ($plan['jobs'] as $job) {
        foreach ($job['projects'] as $name) {
            $dir = "{$inDir}/perf-{$job['job']}";
            $base = loadResult("{$dir}/base/{$name}.json");
            $head = loadResult("{$dir}/head/{$name}.json");
            $judged = judgeProject($base, $head, $plan, $name, $depsChanged);
            if ($base['status'] === 'ok' && $head['status'] === 'ok') {
                $judged = withIssueDiff($judged, $dir, $name);
            }

            $projects[$name] = ['job' => $job['job'], 'base' => slim($base), 'head' => slim($head)] + $judged;
        }
    }

    $worst = max(array_map(static fn(array $p): int => VERDICT_RANK[$p['verdict']], $projects) ?: [1]);

    return [
        'verdict' => ['pass', 'warn', 'fail'][$worst],
        'deps_changed' => $depsChanged,
        'projects' => $projects,
    ];
}

/** @return array<string, mixed> */
function loadResult(string $file): array
{
    try {
        $r = readJsonFile($file);
    } catch (RuntimeException $e) {
        return ['status' => 'missing', 'reason' => is_file($file) ? $e->getMessage() : 'no result file'];
    }

    if (($bad = invalidField($r)) !== null) {
        return ['status' => 'missing', 'reason' => "invalid result ({$bad})"];
    }

    return $r;
}

/** Field types compare consumes ("?" allows absent or null). */
const RESULT_FIELD_TYPES = [
    'reason' => '?string',
    'memory_crash' => '?bool',
    'psalm_mb' => '?number',
    'wall' => '?number',
    'cpu' => '?number',
    'exit_code' => '?int',
    'issue_count' => '?int',
    'log_tail' => '?string',
];

function isType(mixed $v, string $type): bool
{
    return ($v === null && $type[0] === '?') || match (ltrim($type, '?')) {
        'int' => is_int($v),
        'number' => is_int($v) || is_float($v),
        'string' => is_string($v),
        'bool' => is_bool($v),
    };
}

/**
 * First invalid field name, or null.
 *
 * @param array<mixed> $r
 */
function invalidField(array $r): ?string
{
    if (($r['schema'] ?? null) !== 1 || !in_array($r['status'] ?? null, STATUSES, true)) {
        return 'schema or status';
    }

    foreach (RESULT_FIELD_TYPES as $key => $type) {
        if (!isType($r[$key] ?? null, $type)) {
            return $key;
        }
    }

    $runs = $r['runs'] ?? [];
    if (!is_array($runs) || !array_is_list($runs)) {
        return 'runs';
    }

    foreach ($runs as $run) {
        if (!is_array($run) || !isType($run['wall'] ?? null, 'number') || !isType($run['cpu'] ?? null, '?number')) {
            return 'runs';
        }
    }

    if ($r['status'] === 'ok' && !(isType($r['psalm_mb'] ?? null, 'number') && isType($r['wall'] ?? null, 'number'))) {
        return 'ok without psalm_mb or wall';
    }

    return null;
}

/**
 * @param array<string, mixed> $r
 * @return array<string, mixed>
 */
function slim(array $r): array
{
    $keys = ['status', 'reason', 'memory_crash', 'psalm_mb', 'wall', 'cpu', 'exit_code', 'issue_count', 'log_tail'];

    return array_intersect_key($r, array_flip($keys)) + ['runs' => count($r['runs'] ?? [])];
}

/**
 * @param array<string, mixed> $base
 * @param array<string, mixed> $head
 * @param array<mixed> $plan
 * @return array{verdict: string, notes: list<string>, mb_delta: ?float, wall_delta: ?float}
 */
function judgeProject(array $base, array $head, array $plan, string $name, bool $depsChanged): array
{
    $verdict = static fn(string $v, string ...$notes): array => [
        'verdict' => $v,
        'notes' => $notes,
        'mb_delta' => null,
        'wall_delta' => null,
    ];
    foreach (['base' => $base, 'head' => $head] as $side => $r) {
        if (in_array($r['status'], ['missing', 'error'], true)) {
            return $verdict('warn', "{$side}: " . ($r['reason'] ?? $r['status']));
        }
    }

    if ($base['status'] !== 'ok') {
        return $verdict('skip', "base {$base['status']}: not comparable");
    }

    if ($head['status'] !== 'ok') {
        return $verdict(...headFailure($base, $head, $plan, $name));
    }

    return judgeMetrics($base, $head, $depsChanged);
}

/**
 * @param array<string, mixed> $base ok
 * @param array<string, mixed> $head crash or timeout
 * @param array<mixed> $plan
 * @return array{string, string}
 */
function headFailure(array $base, array $head, array $plan, string $name): array
{
    if ($head['status'] === 'timeout') {
        $limit = (float) $plan['timeouts'][$name];
        $baseSize = (float) $base['wall'];
        $what = 'timed out (limit %.0fs), base took %.0fs';
    } elseif ($head['memory_crash'] ?? false) {
        $limit = (float) $plan['memory_limit_mb'];
        $baseSize = (float) $base['psalm_mb'];
        $what = 'ran out of memory (limit %.0f MB), base used %.0f MB';
    } else {
        return ['fail', 'head crashed (' . ($head['reason'] ?? 'exit ' . ($head['exit_code'] ?? '?')) . ')'];
    }

    $note = 'head ' . sprintf($what, $limit, $baseSize);
    $comfortable = $baseSize < LIMIT_HEADROOM * $limit;

    return [$comfortable ? 'fail' : 'warn', $note . ($comfortable ? '' : ', already near the limit')];
}

/**
 * @param array<string, mixed> $base ok
 * @param array<string, mixed> $head ok
 * @return array{verdict: string, notes: list<string>, mb_delta: ?float, wall_delta: ?float}
 */
function judgeMetrics(array $base, array $head, bool $depsChanged): array
{
    $mb = pctDelta((float) $base['psalm_mb'], (float) $head['psalm_mb']);
    $wall = pctDelta((float) $base['wall'], (float) $head['wall']);
    $result = ['verdict' => 'pass', 'notes' => [], 'mb_delta' => $mb, 'wall_delta' => $wall];
    if ($depsChanged) {
        $result['notes'][] = 'composer.json changed: deltas are informational';

        return $result;
    }

    if ($mb !== null && $mb > MB_FAIL_PCT) {
        $result['verdict'] = 'fail';
        $result['notes'][] = sprintf('Psalm memory %+.1f %% (limit +%.0f %%)', $mb, MB_FAIL_PCT);
    } elseif ($wall !== null && $wall > WALL_WARN_PCT) {
        $result['verdict'] = 'warn';
        $result['notes'][] = sprintf('wall time %+.1f %% (limit +%.0f %%)', $wall, WALL_WARN_PCT);
    }

    return $result;
}

function pctDelta(float $base, float $head): ?float
{
    return $base > 0.0 ? ($head - $base) / $base * 100.0 : null;
}

/**
 * Adds the issue diff; unreadable lists make it a warning.
 *
 * @param array{verdict: string, notes: list<string>, mb_delta: ?float, wall_delta: ?float} $judged
 * @return array<string, mixed>
 */
function withIssueDiff(array $judged, string $dir, string $name): array
{
    try {
        $base = readIssues("{$dir}/base/{$name}.issues.json");
        $head = readIssues("{$dir}/head/{$name}.issues.json");
    } catch (RuntimeException $e) {
        $judged['verdict'] = $judged['verdict'] === 'fail' ? 'fail' : 'warn';
        $judged['notes'][] = 'issue lists unavailable: ' . $e->getMessage();

        return $judged + ['issues' => null, 'weighted_issues' => null];
    }

    $weighted = weightedIssues($base, $head);
    if ($weighted['drift']) {
        $judged['notes'][] = 'drift: same issue count, weight changed';
    }

    return $judged + ['issues' => diffIssues($base, $head), 'weighted_issues' => $weighted];
}

/** Unannotated-code suggestions: own bucket, weight 0. */
const PURITY_SUGGESTIONS = [
    'MissingPureAnnotation',
    'MissingImmutableAnnotation',
    'MissingAbstractPureAnnotation',
    'MissingInterfaceImmutableAnnotation',
];
/** Buckets in display order: L1..L8, always (-1), feature (-2), purity. */
const ISSUE_BUCKETS = ['L1', 'L2', 'L3', 'L4', 'L5', 'L6', 'L7', 'L8', 'always', 'feature', 'purity_suggestions'];

/** @param array<string, mixed> $issue */
function issueBucket(array $issue): string
{
    return match (true) {
        in_array($issue['type'], PURITY_SUGGESTIONS, true) => 'purity_suggestions',
        $issue['error_level'] === -1 => 'always',
        $issue['error_level'] === -2 => 'feature',
        default => 'L' . $issue['error_level'],
    };
}

/** Issue weight: level for L1..L8, 10 for always, 0 for feature and purity. */
function bucketWeight(string $bucket): int
{
    return match ($bucket) {
        'always' => 10,
        'feature', 'purity_suggestions' => 0,
        default => (int) substr($bucket, 1),
    };
}

/**
 * Informational only, never feeds a verdict. `drift`: same issue count, different weight.
 *
 * @param list<array<string, mixed>> $base
 * @param list<array<string, mixed>> $head
 * @return array{
 *     base: int, head: int, delta: int, delta_pct: ?float, drift: bool,
 *     by_level: array<string, array{base: int, head: int, delta: int}>
 * }
 */
function weightedIssues(array $base, array $head): array
{
    $counts = static fn(array $issues): array => array_count_values(array_map(issueBucket(...), $issues));
    $weight = static fn(array $counts): int => array_sum(array_map(
        static fn(string $bucket, int $n): int => bucketWeight($bucket) * $n,
        array_keys($counts),
        $counts,
    ));
    [$baseCounts, $headCounts] = [$counts($base), $counts($head)];
    $rank = array_flip(ISSUE_BUCKETS);
    $buckets = array_keys($baseCounts + $headCounts);
    usort($buckets, static fn(string $a, string $b): int => ($rank[$a] ?? 99) <=> ($rank[$b] ?? 99));
    $byLevel = [];
    foreach ($buckets as $bucket) {
        $b = $baseCounts[$bucket] ?? 0;
        $h = $headCounts[$bucket] ?? 0;
        $byLevel[$bucket] = ['base' => $b, 'head' => $h, 'delta' => $h - $b];
    }

    [$baseWeight, $headWeight] = [$weight($baseCounts), $weight($headCounts)];

    return [
        'base' => $baseWeight,
        'head' => $headWeight,
        'delta' => $headWeight - $baseWeight,
        'delta_pct' => pctDelta($baseWeight, $headWeight),
        'by_level' => $byLevel,
        'drift' => count($base) === count($head) && $baseWeight !== $headWeight,
    ];
}

/**
 * @return list<array{type: string, severity: string, file: string, line_from: int, column_from: int, message: string,
 *     error_level: int}>
 * @throws RuntimeException when the file is unreadable or not a list of normalised issues
 */
function readIssues(string $file): array
{
    $issues = readJsonFile($file);
    $types = ['type' => 'string', 'severity' => 'string', 'file' => 'string', 'line_from' => 'int',
        'column_from' => 'int', 'message' => 'string', 'error_level' => 'int'];
    $valid = array_is_list($issues);
    foreach ($issues as $issue) {
        $valid = $valid && is_array($issue);
        foreach ($types as $key => $type) {
            $valid = $valid && isType($issue[$key] ?? null, $type);
        }
    }

    return $valid ? $issues : throw new RuntimeException("{$file} is not a list of issues");
}

/**
 * @param list<array<string, mixed>> $issues
 * @return array<string, int>
 */
function tallyIssues(array $issues): array
{
    $counts = [];
    foreach ($issues as $i) {
        $key = implode(
            "\0",
            [$i['file'], $i['line_from'], $i['column_from'], $i['type'], $i['severity'], $i['message']],
        );
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }

    return $counts;
}

/**
 * One entry per surplus occurrence of each key, sorted.
 *
 * @param array<string, int> $from
 * @param array<string, int> $against
 * @return list<list<string>> exploded keys
 */
function surplus(array $from, array $against): array
{
    $keys = [];
    foreach ($from as $key => $n) {
        array_push($keys, ...array_fill(0, max(0, $n - ($against[$key] ?? 0)), $key));
    }

    sort($keys);

    return array_map(static fn(string $k): array => explode("\0", $k, 6), $keys);
}

/**
 * @param list<array<string, mixed>> $base
 * @param list<array<string, mixed>> $head
 * @return array{
 *     base: int, head: int, added: int, removed: int,
 *     types: array<string, array{added: int, removed: int}>,
 *     added_examples: list<string>, removed_examples: list<string>
 * }
 */
function diffIssues(array $base, array $head): array
{
    $baseTally = tallyIssues($base);
    $headTally = tallyIssues($head);
    $added = surplus($headTally, $baseTally);
    $removed = surplus($baseTally, $headTally);
    $types = [];
    foreach (['added' => $added, 'removed' => $removed] as $kind => $list) {
        foreach ($list as $parts) {
            $types[$parts[3]] ??= ['added' => 0, 'removed' => 0];
            $types[$parts[3]][$kind]++;
        }
    }

    ksort($types);
    $example = static fn(array $p): string => mb_strimwidth(
        "{$p[0]}:{$p[1]} {$p[3]} ({$p[4]}): " . preg_replace('/\s+/u', ' ', $p[5]),
        0,
        MESSAGE_CHARS,
        '…',
    );

    return [
        'base' => count($base),
        'head' => count($head),
        'added' => count($added),
        'removed' => count($removed),
        'types' => $types,
        'added_examples' => array_map($example, array_slice($added, 0, EXAMPLES_PER_LIST)),
        'removed_examples' => array_map($example, array_slice($removed, 0, EXAMPLES_PER_LIST)),
    ];
}
