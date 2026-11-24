<?php

declare(strict_types=1);

/** A file is hot when its analysis CPU grew by at least this many microseconds and by at least HOT_FILE_MIN_PCT. */
const HOT_FILE_MIN_DELTA_US = 200_000;
const HOT_FILE_MIN_PCT = 20.0;
const HOT_FILES_PER_PROJECT = 10;
/** Cap on the hot-files block, so that it leaves room for the issue details. */
const HOT_FILES_BYTES = 12_000;
const HOT_PATH_CHARS = 90;
/** Stage groups of the context rows (names of the `stages` records, repeated ones summed). */
const HOT_STAGE_LABELS = ['scan' => 'scan stages', 'analysis.files' => 'analysis.files', 'taint' => 'taint'];

/**
 * Profile written by `psalm --debug-performance=PATH` (docs/running_psalm/performance.md).
 *
 * @return array<mixed>
 * @throws RuntimeException
 */
function readProfile(string $file): array
{
    if (!is_file($file)) {
        throw new RuntimeException('profile file absent');
    }

    try {
        $profile = readJsonFile($file);
    } catch (RuntimeException) {
        throw new RuntimeException('invalid JSON');
    }

    if (($profile['schema'] ?? null) !== 1 || !is_array($profile['files'] ?? null)
        || !is_array($profile['stages'] ?? null)
    ) {
        throw new RuntimeException('not a schema 1 performance profile');
    }

    return $profile;
}

/**
 * CPU microseconds per analysed file and per stage group (HOT_STAGE_LABELS keys). The stage figures are the main
 * process's CPU, so a run with forked workers (`--threads` above 1) would leave their analysis out of the stage rows;
 * run-shard.sh uses `--threads=1`.
 *
 * @param array<mixed> $profile
 * @return array{files: array<string, int>, stages: array<string, int>}
 */
function profileCpu(array $profile): array
{
    $files = [];
    foreach ($profile['files'] as $f) {
        if (is_array($f) && ($f['op'] ?? null) === 'analysis' && is_string($f['path'] ?? null)) {
            $files[$f['path']] = ($files[$f['path']] ?? 0) + (int) ($f['cpu_us'] ?? 0);
        }
    }

    $stages = array_fill_keys(array_keys(HOT_STAGE_LABELS), 0);
    foreach ($profile['stages'] as $s) {
        $name = is_array($s) ? (string) ($s['name'] ?? '') : '';
        $group = str_starts_with($name, 'scan.') ? 'scan' : $name;
        if (isset($stages[$group])) {
            $stages[$group] += (int) ($s['cpu_us'] ?? 0);
        }
    }

    return ['files' => $files, 'stages' => $stages];
}

/**
 * Stage CPU of both sides, and the files (present on both sides) whose analysis CPU grew most, in microseconds.
 *
 * @param array<mixed> $base
 * @param array<mixed> $head
 * @return array{
 *     stages: list<array{name: string, base_us: int, head_us: int}>,
 *     files: list<array{name: string, base_us: int, head_us: int}>
 * }
 */
function diffProfiles(array $base, array $head): array
{
    $b = profileCpu($base);
    $h = profileCpu($head);
    $row = static fn(string $name, int $baseUs, int $headUs): array
        => ['name' => $name, 'base_us' => $baseUs, 'head_us' => $headUs];

    $stages = [];
    foreach (HOT_STAGE_LABELS as $key => $label) {
        if (max($b['stages'][$key], $h['stages'][$key]) >= 5_000) { // shows as 0.00 s on both sides otherwise
            $stages[] = $row($label, $b['stages'][$key], $h['stages'][$key]);
        }
    }

    $grown = [];
    foreach (array_intersect_key($h['files'], $b['files']) as $path => $headUs) {
        $delta = $headUs - $b['files'][$path];
        if ($delta >= HOT_FILE_MIN_DELTA_US && $headUs * 100.0 >= $b['files'][$path] * (100.0 + HOT_FILE_MIN_PCT)) {
            $grown[(string) $path] = $delta; // digit-only paths are int keys, cast back below
        }
    }

    arsort($grown);
    $files = [];
    foreach (array_slice(array_keys($grown), 0, HOT_FILES_PER_PROJECT) as $path) {
        $files[] = $row((string) $path, $b['files'][$path], $h['files'][$path]);
    }

    return ['stages' => $stages, 'files' => $files];
}

/**
 * Projects with slower files, from the profiles run-shard.sh keeps, the project with the largest total growth first,
 * and the reasons (`{project, side, reason}`) why a compared project has no data: option unsupported, profiling
 * skipped, or an absent or unreadable profile.
 *
 * @param array<mixed> $plan
 * @param array<string, array<string, mixed>> $projects judged projects
 * @return array{
 *     failures: list<array{project: string, side: string, reason: string}>,
 *     projects: array<string, array{
 *         stages: list<array<string, mixed>>, files: list<array<string, mixed>>, delta_us: int
 *     }>
 * }
 */
function collectHotFiles(array $plan, string $inDir, array $projects): array
{
    $failures = $hot = [];
    foreach ($plan['jobs'] as $job) {
        $dir = "{$inDir}/perf-{$job['job']}";
        try {
            $support = readJsonFile("{$dir}/profile-support.json");
        } catch (RuntimeException) {
            continue; // the measure job lost its results; the report already warns about it
        }

        $reasons = is_array($support['reasons'] ?? null) && $support['reasons'] !== []
            ? $support['reasons']
            : ['both' => 'profiling skipped'];
        foreach ($job['projects'] as $name) {
            if ($projects[$name]['base']['status'] !== 'ok' || $projects[$name]['head']['status'] !== 'ok') {
                continue;
            }

            if (($support['profiled'] ?? false) !== true) {
                foreach ($reasons as $side => $reason) {
                    $failures[] = ['project' => $name, 'side' => (string) $side, 'reason' => (string) $reason];
                }

                continue;
            }

            $profiles = [];
            foreach (['base', 'head'] as $side) {
                try {
                    $profiles[$side] = readProfile("{$dir}/{$side}/{$name}.profile.json");
                } catch (RuntimeException $e) {
                    $failures[] = ['project' => $name, 'side' => $side, 'reason' => $e->getMessage()];
                }
            }

            if (count($profiles) === 2) {
                $diff = diffProfiles($profiles['base'], $profiles['head']);
                if ($diff['files'] !== []) {
                    $hot[$name] = $diff + ['delta_us' => array_sum(array_map(
                        static fn(array $f): int => $f['head_us'] - $f['base_us'],
                        $diff['files'],
                    ))];
                }
            }
        }
    }

    uasort($hot, static fn(array $a, array $b): int => $b['delta_us'] <=> $a['delta_us']);

    return ['failures' => $failures, 'projects' => $hot];
}

/**
 * Path cell: the end of the path (cut from the left) as a code span whose fence is longer than any backtick run in
 * it, so the name stays literal; CR and LF become visible markers and cannot end the row.
 */
function hotPathCell(string $path): string
{
    $text = str_replace(["\r", "\n"], ['␍', '␊'], $path);
    if (mb_strlen($text) > HOT_PATH_CHARS) {
        $text = '…' . mb_substr($text, -(HOT_PATH_CHARS - 1));
    }

    preg_match_all('/`+/', $text, $runs);
    $fence = str_repeat('`', max([0, ...array_map(strlen(...), $runs[0])]) + 1);
    $pad = str_starts_with($text, '`') || str_ends_with($text, '`') ? ' ' : '';

    return tableCell("{$fence}{$pad}{$text}{$pad}{$fence}");
}

/**
 * Delta and ratio come from the raw microseconds; only the printed figures are rounded.
 *
 * @param array{name: string, base_us: int, head_us: int} $r
 */
function hotRow(array $r, bool $isFile): string
{
    $delta = round(($r['head_us'] - $r['base_us']) / 1e6, 2);

    return sprintf(
        '| %s | %.2f | %.2f | %s | %s |',
        $isFile ? hotPathCell($r['name']) : '*' . $r['name'] . '*',
        $r['base_us'] / 1e6,
        $r['head_us'] / 1e6,
        $delta === 0.0 ? '0.00' : str_replace('-', '−', sprintf('%+.2f', $delta)),
        $r['base_us'] > 0 ? sprintf('%.2f×', $r['head_us'] / $r['base_us']) : 'n/a',
    );
}

/** "No hot-files data for: symfony (head: invalid JSON), laravel (base: --help failed)"; '' without failures. */
function hotFailuresLine(array $failures): string
{
    $byProject = [];
    foreach ($failures as $f) {
        $byProject[$f['project']][] = "{$f['side']}: {$f['reason']}";
    }

    $items = [];
    foreach ($byProject as $name => $reasons) {
        $items[] = "{$name} (" . implode(', ', $reasons) . ')';
    }

    return $items === [] ? '' : "\nNo hot-files data for: " . implode(', ', $items) . ".\n";
}

function hotFilesFailed(string $message): string
{
    return "\nHot files failed: " . trim((string) preg_replace('/\s+/', ' ', $message)) . "\n";
}

/**
 * Informational "hot files" block: a collapsed details block with a table per project that has slower files (none when
 * nothing grew), then one line listing the projects without data. A failure here shows as one line, so that it never
 * costs the rest of the report.
 *
 * @param array<string, mixed> $hot collectHotFiles() result, or ['failed' => message]
 */
function renderHotFiles(array $hot): string
{
    if (isset($hot['failed'])) {
        return hotFilesFailed((string) $hot['failed']);
    }

    try {
        return renderHotFilesBlock($hot);
    } catch (Throwable $e) {
        return hotFilesFailed($e->getMessage());
    }
}

/**
 * @param array<string, mixed> $hot collectHotFiles() result
 */
function renderHotFilesBlock(array $hot): string
{
    $notes = hotFailuresLine($hot['failures']);
    if ($hot['projects'] === []) {
        return $notes;
    }

    $header = "| | base (s) | head (s) | Δ (s) | × |\n|:--|--:|--:|--:|--:|";
    $sections = [];
    $omitted = 0;
    $size = 0;
    foreach ($hot['projects'] as $name => $p) {
        $rows = [...array_map(static fn(array $r): string => hotRow($r, false), $p['stages']),
            ...array_map(static fn(array $r): string => hotRow($r, true), $p['files'])];
        $section = "**{$name}**\n\n{$header}\n" . implode("\n", $rows) . "\n";
        if ($size + strlen($section) > HOT_FILES_BYTES) {
            $omitted++;
            continue;
        }

        $size += strlen($section);
        $sections[] = $section;
    }

    $names = array_keys($hot['projects']);
    $count = count($names);

    return sprintf(
        "\n<details><summary>Hot files: %d project%s with slower files (%s)</summary>\n\n",
        $count,
        $count === 1 ? '' : 's',
        implode(', ', array_slice($names, 0, 5)) . ($count > 5 ? ', …' : ''),
    ) . sprintf(
        'Up to %d files per project whose analysis CPU grew by at least %.1f s and %.0f %%, most grown first, '
        . 'with the CPU of the scan stages, analysis.files and taint. Informational, never changes the result. '
        . 'Profiles of both sides are in the `perf-report` artifact (`profiles/`); open them at '
        . "https://psalm.dev/profile/.\n\n",
        HOT_FILES_PER_PROJECT,
        HOT_FILE_MIN_DELTA_US / 1e6,
        HOT_FILE_MIN_PCT,
    )
        . implode("\n", $sections)
        . ($omitted > 0 ? "\n{$omitted} project(s) omitted for size; see the profiles.\n" : '')
        . "\n</details>\n" . $notes;
}
