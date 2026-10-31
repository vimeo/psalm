<?php

declare(strict_types=1);

const REPORT_MAX_BYTES = 60_000;
const DETAILS_BUDGET_BYTES = 40_000;
const LOG_TAIL_LINES = 15;
const LOG_TAIL_BYTES = 2_000;
const VERDICT_ICONS = ['pass' => '✅', 'warn' => '⚠️', 'fail' => '❌', 'skip' => '➖'];

/** Signed relative change; 'n/a' for null, '<0.01 %' when a non-zero change rounds to zero. */
function fmtPct(?float $pct, int $precision = 1): string
{
    if ($pct === null) {
        return 'n/a';
    }

    $unit = 10 ** -$precision;

    return abs($pct) > 0.0 && abs($pct) < $unit / 2
        ? sprintf('<%s %%', number_format($unit, $precision))
        : str_replace('-', '−', ($pct < 0 ? '' : '+') . number_format($pct, $precision, '.', '') . ' %');
}

/** Integer with NBSP thousands separator and explicit sign. */
function fmtSigned(int $n): string
{
    return ($n < 0 ? '−' : '+') . fmtInt(abs($n));
}

function fmtInt(int $n): string
{
    return number_format($n, 0, '', "\u{A0}");
}

function inlineCode(string $text): string
{
    return '`' . str_replace('`', "'", $text) . '`';
}

function tableCell(string $text): string
{
    return str_replace(['|', "\n"], ['\\|', ' '], $text);
}

/**
 * @param array<string, mixed> $p
 */
function isOkPair(array $p): bool
{
    return $p['base']['status'] === 'ok' && $p['head']['status'] === 'ok';
}

/**
 * "base → head (Δ)"; "– → head" when base crashed.
 *
 * @param array<string, mixed> $p
 */
function metricCell(array $p, string $key, string $fmt, ?float $delta): string
{
    if (!isOkPair($p)) {
        return $p['head']['status'] === 'ok' ? sprintf("– → {$fmt}", $p['head'][$key]) : '–';
    }

    return sprintf("{$fmt} → {$fmt} (%s)", $p['base'][$key], $p['head'][$key], fmtPct($delta));
}

/**
 * @param array<string, mixed> $p
 */
function wallCell(array $p): string
{
    $cell = metricCell($p, 'wall', '%.1fs', $p['wall_delta']);

    return $cell !== '–' && max($p['base']['runs'], $p['head']['runs']) > 1 ? $cell . ' re-run' : $cell;
}

/**
 * @param array<string, mixed> $p
 */
function issuesCell(array $p): string
{
    $i = $p['issues'] ?? null;
    if ($i === null) {
        return $p['head']['status'] === 'ok' && !isOkPair($p) ? sprintf('– → %d', $p['head']['issue_count']) : '–';
    }

    return sprintf('%d → %d (+%d −%d)', $i['base'], $i['head'], $i['added'], $i['removed']);
}

/**
 * "base → head (Δ, Δ %)"; "(=)" when unchanged.
 *
 * @param array<string, mixed> $p
 */
function weightedCell(array $p): string
{
    $w = $p['weighted_issues'] ?? null;

    return $w === null ? '–' : weightedSummary($w);
}

/**
 * @param array{base: int, head: int, delta: int, delta_pct: ?float} $w
 */
function weightedSummary(array $w): string
{
    $change = $w['delta'] === 0
        ? '='
        : fmtSigned($w['delta']) . ($w['delta_pct'] === null ? '' : ', ' . fmtPct($w['delta_pct'], 2));

    return sprintf('%s → %s (%s)', fmtInt($w['base']), fmtInt($w['head']), $change);
}

/**
 * Pooled Σhead / Σbase over projects compared on both sides.
 *
 * @param array<string, mixed> $summary
 */
function corpusWeightedLine(array $summary): string
{
    $base = $head = $changed = $n = 0;
    $largest = null;
    foreach ($summary['projects'] as $name => $p) {
        $w = $p['weighted_issues'] ?? null;
        if ($w === null) {
            continue;
        }

        $n++;
        $base += $w['base'];
        $head += $w['head'];
        $changed += $w['delta'] !== 0 ? 1 : 0;
        if ($w['delta_pct'] !== null && abs($w['delta_pct']) > abs($largest[1] ?? 0.0)) {
            $largest = [$name, $w['delta_pct']];
        }
    }

    if ($n === 0) {
        return '';
    }

    $pooled = ['base' => $base, 'head' => $head, 'delta' => $head - $base, 'delta_pct' => pctDelta($base, $head)];

    return sprintf(
        "Σ %s · %d/%d projects changed%s\n\n",
        weightedSummary($pooled),
        $changed,
        $n,
        $largest === null ? '' : " · largest: {$largest[0]} " . fmtPct($largest[1], 2),
    );
}

/**
 * Non-zero per-level deltas, e.g. "L1 +100 · always +2".
 *
 * @param array<string, mixed> $p
 */
function levelDeltas(array $p): string
{
    $deltas = [];
    foreach ($p['weighted_issues']['by_level'] ?? [] as $label => $l) {
        if ($l['delta'] !== 0) {
            $deltas[] = str_replace('_', ' ', $label) . ' ' . fmtSigned($l['delta']);
        }
    }

    return implode(' · ', $deltas);
}

/**
 * @param array<string, mixed> $summary
 */
function comparedCount(array $summary): int
{
    return count(array_filter(
        $summary['projects'],
        static fn(array $p): bool => $p['base']['status'] === 'ok'
            && in_array($p['head']['status'], ['ok', 'crash', 'timeout'], true),
    ));
}

/**
 * @param array<string, mixed> $p
 */
function detailsFor(string $name, array $p): string
{
    $parts = [];
    $i = $p['issues'] ?? null;
    if (($levels = levelDeltas($p)) !== '') {
        $parts[] = '**Weighted issues** ' . weightedCell($p) . ": {$levels}";
    }

    if ($i !== null && ($i['added'] > 0 || $i['removed'] > 0)) {
        $rows = ["| Issue type | added | removed |", '|---|---|---|'];
        foreach ($i['types'] as $type => $n) {
            $rows[] = '| ' . tableCell($type) . " | {$n['added']} | {$n['removed']} |";
        }

        $parts[] = implode("\n", $rows);

        foreach (['added', 'removed'] as $kind) {
            if ($i["{$kind}_examples"] !== []) {
                $more = $i[$kind] - count($i["{$kind}_examples"]);
                $items = array_map(static fn(string $e): string => '- ' . inlineCode($e), $i["{$kind}_examples"]);
                $parts[] = "**" . ucfirst($kind) . "** ({$i[$kind]})\n\n" . implode("\n", $items)
                    . ($more > 0 ? "\n- … and {$more} more" : '');
            }
        }
    }

    foreach (['head', 'base'] as $side) {
        if (($p[$side]['log_tail'] ?? null) !== null && $p[$side]['status'] !== 'ok') {
            $lines = array_slice(explode("\n", (string) $p[$side]['log_tail']), -LOG_TAIL_LINES);
            $log = str_replace('```', "'''", substr(implode("\n", $lines), -LOG_TAIL_BYTES));
            $parts[] = "**{$side} log tail**\n\n```text\n{$log}\n```";
        }
    }

    if ($parts === []) {
        return '';
    }

    $summary = sprintf('%s %s', VERDICT_ICONS[$p['verdict']], $name);

    return "<details><summary>{$summary}</summary>\n\n" . implode("\n\n", $parts) . "\n\n</details>\n";
}

/**
 * @param array<string, mixed> $summary result of compareAll() plus base_sha and head_sha
 */
function renderReport(array $summary, ?string $runUrl): string
{
    $total = count($summary['projects']);
    $out = sprintf(
        "## %s Perf: %s, head `%s`, %d/%d compared\n\n",
        VERDICT_ICONS[$summary['verdict']],
        $summary['verdict'],
        substr($summary['head_sha'], 0, 12),
        comparedCount($summary),
        $total,
    );
    $out .= corpusWeightedLine($summary);
    $out .= "| Project | Psalm MB | Wall | Issues | Weighted issues | Status |\n|---|---|---|---|---|---|\n";
    foreach ($summary['projects'] as $name => $p) {
        $out .= sprintf(
            "| %s | %s | %s | %s | %s | %s |\n",
            $name,
            metricCell($p, 'psalm_mb', '%.0f', $p['mb_delta']),
            wallCell($p),
            issuesCell($p),
            weightedCell($p),
            trim(VERDICT_ICONS[$p['verdict']] . ' ' . tableCell(implode('; ', $p['notes']))),
        );
    }

    $out .= "\nWeighted issues = issues weighted by error level (levels 1–8 as is, always-errors 10, "
        . "feature-specific and purity suggestions 0). The projects' own code is pinned (their dependencies are "
        . "resolved at run time with `composer update`, identically for base and head), so a change means Psalm "
        . "reports more, fewer or differently serious issues. Informational; a rise is not necessarily a regression.\n";

    $out .= "\nMemory is Psalm's own peak figure (threads=1). Wall time never fails the check; above +20 % it warns. "
        . "Base and head ran on the same runner, expect ±10 % noise; \"re-run\" means a reversed second run was "
        . "taken and the faster kept.\n";
    $out .= "\n➖ = not comparable (base crashed or timed out); it counts as ⚠️ overall.\n";

    $out .= appendDetails($summary, strlen($out));

    $footer = "\nBase (merge-base): `" . substr($summary['base_sha'], 0, 12) . '`';
    $footer .= $summary['deps_changed']
        ? ". composer.json changed in this PR, so head dependencies were re-resolved; "
            . "memory and time deltas are informational.\n"
        : ".\n";
    if ($runUrl !== null) {
        $footer .= "[Workflow run]({$runUrl})\n";
    }

    return $out . $footer;
}

/**
 * Details in table order while they fit the budgets.
 *
 * @param array<string, mixed> $summary
 */
function appendDetails(array $summary, int $usedBytes): string
{
    $details = '';
    $omitted = 0;
    foreach ($summary['projects'] as $name => $p) {
        $block = detailsFor($name, $p);
        $fits = strlen($details) + strlen($block) <= DETAILS_BUDGET_BYTES
            && $usedBytes + strlen($details) + strlen($block) <= REPORT_MAX_BYTES - 1_000;
        if ($block === '' || $fits) {
            $details .= $block;
        } else {
            $omitted++;
        }
    }

    $note = $omitted > 0 ? "\n{$omitted} detail block(s) omitted for size; see summary.json in the artifact.\n" : '';

    return ($details === '' ? '' : "\n" . $details) . $note;
}

/**
 * @param list<string> $args
 */
function runCompare(array $args): int
{
    $names = ['plan', 'in', 'base-sha', 'head-sha', 'deps-changed', 'out-md', 'out-json', 'run-url'];
    $options = parseOptions($args, $names);
    requireOptions($options, array_slice($names, 0, 7));
    $summary = compareAll(readJsonFile($options['plan']), $options['in'], $options['deps-changed'] === '1');
    $summary += ['base_sha' => $options['base-sha'], 'head_sha' => $options['head-sha']];
    writeJsonFile($options['out-json'], ['schema' => 1] + $summary);
    if (file_put_contents($options['out-md'], renderReport($summary, $options['run-url'] ?? null)) === false) {
        throw new RuntimeException("cannot write {$options['out-md']}");
    }

    return 0;
}
