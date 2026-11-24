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
    $profile = readJsonFile($file);
    if (($profile['schema'] ?? null) !== 1 || !is_array($profile['files'] ?? null)
        || !is_array($profile['stages'] ?? null)
    ) {
        throw new RuntimeException("{$file} is not a schema 1 performance profile");
    }

    return $profile;
}

/**
 * CPU microseconds per analysed file and per stage group (HOT_STAGE_LABELS keys). Needs `--threads=1`, else the
 * file records of forked workers are summed per path as well.
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
 * Stage CPU of both sides, and the files (present on both sides) whose analysis CPU grew most, in seconds.
 *
 * @param array<mixed> $base
 * @param array<mixed> $head
 * @return array{
 *     stages: list<array{name: string, base: float, head: float}>,
 *     files: list<array{name: string, base: float, head: float}>
 * }
 */
function diffProfiles(array $base, array $head): array
{
    $b = profileCpu($base);
    $h = profileCpu($head);
    $seconds = static fn(int $us): float => round($us / 1e6, 3);
    $row = static fn(string $name, int $baseUs, int $headUs): array
        => ['name' => $name, 'base' => $seconds($baseUs), 'head' => $seconds($headUs)];

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
            $grown[(string) $path] = $delta;
        }
    }

    arsort($grown);
    $files = [];
    foreach (array_slice(array_keys($grown), 0, HOT_FILES_PER_PROJECT) as $path) {
        $files[] = $row($path, $b['files'][$path], $h['files'][$path]);
    }

    return ['stages' => $stages, 'files' => $files];
}

/**
 * Hot files of every project compared on both sides, from the profiles run-shard.sh keeps. `unsupported` lists the
 * sides whose Psalm lacks the option (then no side was profiled); `missing` the projects without readable profiles.
 *
 * @param array<mixed> $plan
 * @param array<string, array<string, mixed>> $projects judged projects
 * @return array{
 *     unsupported: list<string>, missing: list<string>,
 *     projects: array<string, array{stages: list<array<string, mixed>>, files: list<array<string, mixed>>}>
 * }
 */
function collectHotFiles(array $plan, string $inDir, array $projects): array
{
    $unsupported = $missing = $hot = [];
    foreach ($plan['jobs'] as $job) {
        $dir = "{$inDir}/perf-{$job['job']}";
        try {
            $support = readJsonFile("{$dir}/profile-support.json");
        } catch (RuntimeException) {
            continue; // the measure job lost its results; the report already warns about it
        }

        $lacking = array_values(array_filter(
            ['base', 'head'],
            static fn(string $side): bool => ($support[$side] ?? false) !== true,
        ));
        $unsupported = array_values(array_unique([...$unsupported, ...$lacking]));
        if ($lacking !== []) {
            continue;
        }

        foreach ($job['projects'] as $name) {
            $p = $projects[$name];
            if ($p['base']['status'] !== 'ok' || $p['head']['status'] !== 'ok') {
                continue;
            }

            try {
                $hot[$name] = diffProfiles(
                    readProfile("{$dir}/base/{$name}.profile.json"),
                    readProfile("{$dir}/head/{$name}.profile.json"),
                );
            } catch (RuntimeException) {
                $missing[] = $name;
            }
        }
    }

    return ['unsupported' => $unsupported, 'missing' => $missing, 'projects' => $hot];
}

/** Path cell: the end of the path in code font, cut from the left. */
function hotPathCell(string $path): string
{
    return tableCell(inlineCode(
        mb_strlen($path) > HOT_PATH_CHARS ? '…' . mb_substr($path, -(HOT_PATH_CHARS - 1)) : $path,
    ));
}

/**
 * @param array{name: string, base: float, head: float} $r
 */
function hotRow(array $r, bool $isFile): string
{
    $delta = round($r['head'] - $r['base'], 2) + 0.0; // no "-0.00"

    return sprintf(
        '| %s | %.2f | %.2f | %s | %s |',
        $isFile ? hotPathCell($r['name']) : '*' . $r['name'] . '*',
        $r['base'],
        $r['head'],
        $delta === 0.0 ? '0.00' : str_replace('-', '−', sprintf('%+.2f', $delta)),
        $r['base'] > 0 ? sprintf('%.2f×', $r['head'] / $r['base']) : 'n/a',
    );
}

/**
 * Informational "hot files" block: one line when the profile option is unsupported or no profile was read,
 * else a collapsed details block with a table per project. Empty when there is nothing to say.
 *
 * @param array<string, mixed> $hot collectHotFiles() result
 */
function renderHotFiles(array $hot): string
{
    if ($hot['unsupported'] !== []) {
        $sides = implode(' and ', array_map(
            static fn(string $side): string => $side === 'base' ? 'base (merge-base)' : $side,
            $hot['unsupported'],
        ));

        return "\nHot files are not shown: {$sides} Psalm lacks `--debug-performance=PATH`.\n";
    }

    if ($hot['projects'] === []) {
        return $hot['missing'] === [] ? '' : "\nHot files are not shown: no performance profile could be read.\n";
    }

    $header = "| | base (s) | head (s) | Δ (s) | × |\n|:--|--:|--:|--:|--:|";
    $sections = [];
    $omitted = 0;
    $size = 0;
    foreach ($hot['projects'] as $name => $p) {
        $rows = [...array_map(static fn(array $r): string => hotRow($r, false), $p['stages']),
            ...array_map(static fn(array $r): string => hotRow($r, true), $p['files'])];
        $section = "**{$name}**\n\n{$header}\n" . implode("\n", $rows) . "\n"
            . ($p['files'] === [] ? "\nNo file grew.\n" : '');
        if ($size + strlen($section) > HOT_FILES_BYTES) {
            $omitted++;
            continue;
        }

        $size += strlen($section);
        $sections[] = $section;
    }

    return "\n<details><summary>Hot files: analysis CPU per file, base → head</summary>\n\n"
        . sprintf(
            'Up to %d files whose analysis CPU grew by at least %.1f s and %.0f %%, plus the CPU of the stages. '
            . 'Informational, never changes the result. Profiles of both sides are in the `perf-report` artifact '
            . "(`profiles/`); open them at https://psalm.dev/perf/.\n\n",
            HOT_FILES_PER_PROJECT,
            HOT_FILE_MIN_DELTA_US / 1e6,
            HOT_FILE_MIN_PCT,
        )
        . implode("\n", $sections)
        . ($omitted > 0 ? "\n{$omitted} project(s) omitted for size; see the profiles.\n" : '')
        . ($hot['missing'] === [] ? '' : "\nNo readable profile for: " . implode(', ', $hot['missing']) . ".\n")
        . "\n</details>\n";
}
