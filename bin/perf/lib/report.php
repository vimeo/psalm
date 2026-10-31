<?php

declare(strict_types=1);

const REPORT_MAX_BYTES = 60_000;
const DETAILS_BUDGET_BYTES = 40_000;
const LOG_TAIL_LINES = 15;
const LOG_TAIL_BYTES = 2_000;
/** Wall changes within ±this are runner noise; a larger drop counts as an improvement. */
const WALL_NOISE_PCT = 10.0;
const VERDICT_ICONS = ['pass' => '✅', 'warn' => '⚠️', 'fail' => '❌', 'skip' => '💥'];
/** Table and details order: most severe first. */
const VERDICT_ORDER = ['fail' => 0, 'warn' => 1, 'skip' => 2, 'pass' => 3];
const TABLE_LEGEND = "<details><summary>How to read this table</summary>\n\n"
    . "- **Memory**: Psalm's own peak figure, `threads=1`. More than +5 % fails, unless the PR changes "
    . "`composer.json`.\n"
    . '- **Wall**: base and head run on the same runner; ±10 % is noise. Above +20 % warns, never fails. '
    . "*re-run*: a reversed second run was taken and the faster kept.\n"
    . '- **Issues**: head count, then $\color{red}{\textsf{+added}}$ $\color{green}{\textsf{-removed}}$ '
    . "against base.\n"
    . '- **Weighted**: issues weighted by error level (levels 1–8 as is, always-errors 10, feature-specific and '
    . "purity suggestions 0). The projects' own code is pinned and their dependencies resolve identically for base "
    . 'and head, so a change means Psalm reports more, fewer or differently serious issues. A rise is not '
    . "necessarily a regression.\n"
    . "- **Total**: sum over the projects compared on both sides. Thresholds apply per project, not to the total.\n"
    . '- **Colors**: $\color{red}{\textsf{red}}$ past a fail or warn threshold (or more issues), '
    . '$\color{green}{\textsf{green}}$ an improvement past noise (or fewer issues), '
    . '$\color{gray}{\textsf{gray}}$ noise.' . "\n"
    . '- **Performance**: *regressed* if a head run crashed or timed out or a memory or wall delta is red, '
    . 'else *improved* if one is green, else *unchanged*. Its icon is the check result: ❌ fail · ⚠️ warn · '
    . "✅ pass; the icon next to each project is that project's result, 💥 = not comparable: base crashed or "
    . "timed out (counts as ⚠️).\n"
    . '- **Issues** (heading): the total weighted change, *increased* · *decreased* · *unchanged*. '
    . "Informational, never fails the check.\n\n"
    . '</details>';

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

/** Single-line text safe in a table cell or <summary>. */
function tableCell(string $text): string
{
    return str_replace(['|', "\n"], ['\\|', ' '], $text);
}

/**
 * GitHub math text in a color. Markdown unescapes `\\%` to LaTeX's `\%`; a bare `%` would start a LaTeX comment.
 */
function colored(string $text, string $color): string
{
    return '$\color{' . $color . '}{\textsf{' . str_replace('%', '\\\\%', $text) . '}}$';
}

/**
 * " +7.7%" in red above $worse, green below -$better, gray between; '' when it rounds to zero.
 */
function pctBadge(?float $pct, float $worse, float $better): string
{
    if ($pct === null || abs($pct) < 0.05) {
        return '';
    }

    $color = $pct > $worse ? 'red' : ($pct < -$better ? 'green' : 'gray');

    return ' ' . colored(sprintf('%+.1f%%', $pct), $color);
}

/** " +14" in red, " -36" in green; '' for zero. */
function countBadge(int $delta): string
{
    return $delta === 0
        ? ''
        : ' ' . colored(($delta > 0 ? '+' : '-') . fmtInt(abs($delta)), $delta > 0 ? 'red' : 'green');
}

/** " +9 -3"; '' when nothing was added or removed. */
function addedRemovedBadge(int $added, int $removed): string
{
    return $added === 0 && $removed === 0
        ? ''
        : ' ' . colored("+{$added}", $added > 0 ? 'red' : 'gray') . ' '
            . colored("-{$removed}", $removed > 0 ? 'green' : 'gray');
}

/**
 * @param array<string, mixed> $p
 */
function isOkPair(array $p): bool
{
    return $p['base']['status'] === 'ok' && $p['head']['status'] === 'ok';
}

/**
 * Head figure plus its delta; head figure alone when base is not comparable; '–' when head has none.
 *
 * @param array<string, mixed> $p
 */
function memoryCell(array $p): string
{
    if ($p['head']['status'] !== 'ok') {
        return '–';
    }

    $cell = fmtInt((int) round($p['head']['psalm_mb']));

    return isOkPair($p) ? $cell . pctBadge($p['mb_delta'], MB_FAIL_PCT, MB_FAIL_PCT) : $cell;
}

/**
 * @param array<string, mixed> $p
 */
function wallCell(array $p): string
{
    if ($p['head']['status'] !== 'ok') {
        return '–';
    }

    $cell = sprintf('%.1f', $p['head']['wall']);
    if (!isOkPair($p)) {
        return $cell;
    }

    return $cell . pctBadge($p['wall_delta'], WALL_WARN_PCT, WALL_NOISE_PCT)
        . (max($p['base']['runs'], $p['head']['runs']) > 1 ? ' re-run' : '');
}

/**
 * @param array<string, mixed> $p
 */
function issuesCell(array $p): string
{
    $i = $p['issues'] ?? null;
    if ($i !== null) {
        return fmtInt($i['head']) . addedRemovedBadge($i['added'], $i['removed']);
    }

    return $p['head']['status'] === 'ok' && ($p['head']['issue_count'] ?? null) !== null
        ? fmtInt($p['head']['issue_count'])
        : '–';
}

/**
 * @param array<string, mixed> $p
 */
function weightedCell(array $p): string
{
    $w = $p['weighted_issues'] ?? null;

    return $w === null ? '–' : fmtInt($w['head']) . countBadge($w['delta']);
}

/**
 * "base → head (Δ, Δ %)"; "(=)" when unchanged.
 *
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
 * Sums over the projects compared on both sides; null when there are none.
 *
 * @param array<string, array<string, mixed>> $projects
 */
function totalRow(array $projects): ?string
{
    $mb = $wall = ['base' => 0.0, 'head' => 0.0];
    $issues = ['head' => 0, 'added' => 0, 'removed' => 0];
    $weighted = ['head' => 0, 'delta' => 0];
    $n = 0;
    foreach (array_filter($projects, isOkPair(...)) as $p) {
        $n++;
        foreach (['base', 'head'] as $side) {
            $mb[$side] += (float) $p[$side]['psalm_mb'];
            $wall[$side] += (float) $p[$side]['wall'];
        }

        // Same head count as issuesCell(), which falls back to issue_count when the issue lists are unavailable.
        $issues['head'] += $p['issues']['head'] ?? $p['head']['issue_count'] ?? 0;
        $issues['added'] += $p['issues']['added'] ?? 0;
        $issues['removed'] += $p['issues']['removed'] ?? 0;

        foreach ($weighted as $key => $_) {
            $weighted[$key] += $p['weighted_issues'][$key] ?? 0;
        }
    }

    if ($n === 0) {
        return null;
    }

    return sprintf(
        "| **Total** | %s%s | %.1f%s | %s%s | %s%s |\n",
        fmtInt((int) round($mb['head'])),
        pctBadge(pctDelta($mb['base'], $mb['head']), MB_FAIL_PCT, MB_FAIL_PCT),
        $wall['head'],
        pctBadge(pctDelta($wall['base'], $wall['head']), WALL_WARN_PCT, WALL_NOISE_PCT),
        fmtInt($issues['head']),
        addedRemovedBadge($issues['added'], $issues['removed']),
        fmtInt($weighted['head']),
        countBadge($weighted['delta']),
    );
}

/**
 * regressed / improved / unchanged, matching the red and green deltas; 'not compared' when nothing ran on both sides.
 *
 * @param array<string, array<string, mixed>> $projects
 */
function performanceWord(array $projects): string
{
    $compared = $improved = false;
    foreach ($projects as $p) {
        if ($p['base']['status'] === 'ok' && in_array($p['head']['status'], ['crash', 'timeout'], true)) {
            return 'regressed';
        }

        if (!isOkPair($p)) {
            continue;
        }

        $compared = true;
        $mb = $p['mb_delta'] ?? 0.0;
        $wall = $p['wall_delta'] ?? 0.0;
        if ($mb > MB_FAIL_PCT || $wall > WALL_WARN_PCT) {
            return 'regressed';
        }

        $improved = $improved || $mb < -MB_FAIL_PCT || $wall < -WALL_NOISE_PCT;
    }

    return $compared ? ($improved ? 'improved' : 'unchanged') : 'not compared';
}

/**
 * Direction of the total weighted change; null when no project has issue lists on both sides.
 *
 * @param array<string, array<string, mixed>> $projects
 */
function issuesWord(array $projects): ?string
{
    $deltas = array_filter(array_map(
        static fn(array $p): ?int => $p['weighted_issues']['delta'] ?? null,
        $projects,
    ), static fn(?int $d): bool => $d !== null);
    if ($deltas === []) {
        return null;
    }

    $sum = array_sum($deltas);

    return $sum > 0 ? 'increased' : ($sum < 0 ? 'decreased' : 'unchanged');
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
 * Last lines of a log without runner checkout paths and without PHP's second copy of each error line.
 */
function logTail(string $log): string
{
    $log = (string) preg_replace(
        ['~/\S*?/psalm-(?:base|head)/~', '/^PHP (?=[A-Z][a-z]+(?: error)?:)/m', '/^([A-Z][a-z]+(?: error)?):\s+/m'],
        ['', '', '$1: '],
        $log,
    );
    $lines = array_slice(array_values(array_unique(explode("\n", trim($log)))), -LOG_TAIL_LINES);

    return str_replace('```', "'''", substr(implode("\n", $lines), -LOG_TAIL_BYTES));
}

/**
 * Details body: figures, issue diff and log tails; '' when there is nothing beyond the table.
 *
 * @param array<string, mixed> $p
 */
function detailsBody(array $p): string
{
    $parts = [];
    $i = $p['issues'] ?? null;
    if (($levels = levelDeltas($p)) !== '') {
        $parts[] = '**Weighted** ' . weightedSummary($p['weighted_issues']) . ": {$levels}";
    }

    if ($i !== null && ($i['added'] > 0 || $i['removed'] > 0)) {
        $rows = ['| Issue type | added | removed |', '|:--|--:|--:|'];
        foreach ($i['types'] as $type => $n) {
            $rows[] = '| ' . tableCell($type) . " | {$n['added']} | {$n['removed']} |";
        }

        $parts[] = implode("\n", $rows);

        foreach (['added', 'removed'] as $kind) {
            if ($i["{$kind}_examples"] !== []) {
                $more = $i[$kind] - count($i["{$kind}_examples"]);
                $items = array_map(static fn(string $e): string => '- ' . inlineCode($e), $i["{$kind}_examples"]);
                $parts[] = '**' . ucfirst($kind) . "** ({$i[$kind]})\n\n" . implode("\n", $items)
                    . ($more > 0 ? "\n- … and {$more} more" : '');
            }
        }
    }

    $logs = [];
    foreach (['head', 'base'] as $side) {
        if (($p[$side]['log_tail'] ?? null) !== null && $p[$side]['status'] !== 'ok') {
            $logs[$side] = logTail((string) $p[$side]['log_tail']);
        }
    }

    if (count($logs) === 2 && $logs['head'] === $logs['base']) {
        $logs = ['base and head' => $logs['head']];
    }

    foreach ($logs as $side => $log) {
        $parts[] = "**{$side} log tail**\n\n```text\n{$log}\n```";
    }

    if ($parts === [] && $p['notes'] === []) {
        return '';
    }

    if (isOkPair($p)) {
        array_unshift($parts, sprintf(
            '**Memory** %s → %s MB (%s) · **Wall** %.1f → %.1f s (%s)',
            fmtInt((int) round($p['base']['psalm_mb'])),
            fmtInt((int) round($p['head']['psalm_mb'])),
            fmtPct($p['mb_delta']),
            $p['base']['wall'],
            $p['head']['wall'],
            fmtPct($p['wall_delta']),
        ));
    }

    return implode("\n\n", $parts);
}

/**
 * "Psalm memory +7.7 % (limit +5 %); issues +9 −3": notes and issue changes.
 *
 * @param array<string, mixed> $p
 */
function detailsTitle(array $p): string
{
    $title = $p['notes'];
    $i = $p['issues'] ?? null;
    if ($i !== null && ($i['added'] > 0 || $i['removed'] > 0)) {
        $title[] = "issues +{$i['added']} −{$i['removed']}";
    }

    return tableCell(implode('; ', $title));
}

/**
 * @param array<string, mixed> $summary result of compareAll() plus base_sha and head_sha
 */
function renderReport(array $summary, ?string $runUrl): string
{
    $projects = $summary['projects'];
    uasort(
        $projects,
        static fn(array $a, array $b): int => VERDICT_ORDER[$a['verdict']] <=> VERDICT_ORDER[$b['verdict']],
    );

    $issuesWord = issuesWord($projects);
    $out = sprintf(
        "## %s Performance: %s%s\n\n",
        VERDICT_ICONS[$summary['verdict']],
        performanceWord($projects),
        $issuesWord === null ? '' : " · Issues: {$issuesWord}",
    );
    $out .= "| Project | Memory (MB) | Wall (s) | Issues | Weighted |\n|:--|--:|--:|--:|--:|\n";
    foreach ($projects as $name => $p) {
        $out .= sprintf(
            "| %s %s | %s | %s | %s | %s |\n",
            VERDICT_ICONS[$p['verdict']],
            $name,
            memoryCell($p),
            wallCell($p),
            issuesCell($p),
            weightedCell($p),
        );
    }

    $out .= totalRow($projects) ?? '';
    $out .= "\n" . TABLE_LEGEND . "\n";

    $footer = $summary['deps_changed']
        ? "\ncomposer.json changed in this PR, so head dependencies were re-resolved; "
            . "memory and time deltas are informational.\n"
        : '';
    $footer .= sprintf(
        "\n<sub>head %s · base %s (merge-base)%s</sub>\n",
        substr($summary['head_sha'], 0, 12),
        substr($summary['base_sha'], 0, 12),
        $runUrl === null ? '' : " · [workflow run]({$runUrl})",
    );

    return $out . appendDetails($projects, strlen($out) + strlen($footer)) . $footer;
}

/**
 * Details in table order while they fit the budgets; projects with the same verdict, notes and body share a block.
 * A note without anything to expand becomes a plain line.
 *
 * @param array<string, array<string, mixed>> $projects
 */
function appendDetails(array $projects, int $usedBytes): string
{
    $groups = [];
    foreach ($projects as $name => $p) {
        $body = detailsBody($p);
        $title = detailsTitle($p);
        if ($body === '' && $title === '') {
            continue;
        }

        $key = implode("\0", [$p['verdict'], $title, $body]);
        $groups[$key] ??= ['verdict' => $p['verdict'], 'title' => $title, 'body' => $body, 'names' => []];
        $groups[$key]['names'][] = $name;
    }

    $details = '';
    $omitted = 0;
    foreach ($groups as $g) {
        $summary = VERDICT_ICONS[$g['verdict']] . ' ' . implode(', ', $g['names'])
            . ($g['title'] === '' ? '' : ": {$g['title']}");
        $block = $g['body'] === ''
            ? "\n{$summary}\n\n"
            : "<details><summary>{$summary}</summary>\n\n{$g['body']}\n\n</details>\n";
        $fits = strlen($details) + strlen($block) <= DETAILS_BUDGET_BYTES
            && $usedBytes + strlen($details) + strlen($block) <= REPORT_MAX_BYTES - 1_000;
        if ($fits) {
            $details .= $block;
        } else {
            $omitted += count($g['names']);
        }
    }

    $note = $omitted > 0 ? "\n{$omitted} detail block(s) omitted for size; see summary.json in the artifact.\n" : '';

    return $details . $note;
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
