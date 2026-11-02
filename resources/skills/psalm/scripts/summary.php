<?php

declare(strict_types=1);

const USAGE = <<<'TXT'
Usage: php summary.php REPORT.json [options]

REPORT.json is written by `psalm --report=REPORT.json` or `psalm --output-format=json > REPORT.json`;
"-" reads it from stdin.

Without filters, prints issue counts per type and the files with the most issues.
With a filter, lists the matching issues as `SEVERITY file:line:col Type: message`.

  --type=A,B        only these issue types
  --file=PATH       only issues in files whose path contains PATH
  --changed[=REF]   only issues on lines changed since REF (default HEAD, working tree included)
  --snippets        print the code of each listed issue
  --info            include info-level issues
  --limit=N         at most N lines per section (default 30)

TXT;

/**
 * @param list<string> $argv
 * @return array{report: string, types: list<string>, file: ?string, changed: ?string, snippets: bool,
 *     info: bool, limit: int}
 */
function parseArguments(array $argv): array
{
    $options = [
        'report' => '',
        'types' => [],
        'file' => null,
        'changed' => null,
        'snippets' => false,
        'info' => false,
        'limit' => 30,
    ];
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '-h' || $arg === '--help') {
            fwrite(STDOUT, USAGE);
            exit(0);
        }
        if (preg_match('/^--type=(.+)$/', $arg, $m)) {
            $options['types'] = array_values(array_filter(array_map('trim', explode(',', $m[1]))));
        } elseif (preg_match('/^--file=(.+)$/', $arg, $m)) {
            $options['file'] = str_replace('\\', '/', $m[1]);
        } elseif ($arg === '--changed') {
            $options['changed'] = 'HEAD';
        } elseif (preg_match('/^--changed=(.+)$/', $arg, $m)) {
            $options['changed'] = $m[1];
        } elseif ($arg === '--snippets') {
            $options['snippets'] = true;
        } elseif ($arg === '--info') {
            $options['info'] = true;
        } elseif (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
            $options['limit'] = max(1, (int) $m[1]);
        } elseif ($arg !== '' && $arg[0] !== '-' || $arg === '-') {
            $options['report'] = $arg;
        } else {
            fail("Unknown option $arg\n\n" . USAGE);
        }
    }
    if ($options['report'] === '') {
        fail(USAGE);
    }

    return $options;
}

function fail(string $message): never
{
    fwrite(STDERR, rtrim($message) . "\n");
    exit(1);
}

/**
 * @return list<array<array-key, mixed>>
 */
function readReport(string $path): array
{
    $json = $path === '-' ? stream_get_contents(STDIN) : @file_get_contents($path);
    if ($json === false) {
        fail("Cannot read $path");
    }
    $issues = json_decode($json, true);
    if (!is_array($issues) || !array_is_list($issues)) {
        fail("$path is not a Psalm JSON report (use --report=FILE.json or --output-format=json)");
    }

    return array_values(array_filter($issues, 'is_array'));
}

/**
 * Lines changed since $ref, keyed by absolute normalised path; `true` marks an untracked file.
 *
 * @return array<string, true|array<int, true>>
 */
function changedLines(string $ref): array
{
    $root = trim(git('rev-parse --show-toplevel'));
    if ($root === '') {
        fail('--changed needs a git repository');
    }
    $changed = [];
    $diff = git('diff --no-color --no-ext-diff -U0 ' . escapeshellarg($ref) . ' --');
    $file = null;
    foreach (explode("\n", $diff) as $line) {
        if (str_starts_with($line, '+++ ')) {
            $file = $line === '+++ /dev/null' ? null : normalisePath($root . '/' . substr($line, 6));
        } elseif ($file !== null && preg_match('/^@@ -\S+ \+(\d+)(?:,(\d+))? @@/', $line, $m)) {
            $start = (int) $m[1];
            $count = isset($m[2]) ? (int) $m[2] : 1;
            for ($i = $start; $i < $start + $count; $i++) {
                $changed[$file][$i] = true;
            }
        }
    }
    $untracked = git('ls-files --others --exclude-standard --full-name');
    foreach (array_filter(explode("\n", $untracked)) as $path) {
        $changed[normalisePath($root . '/' . $path)] = true;
    }

    return $changed;
}

function git(string $arguments): string
{
    $output = [];
    $status = 0;
    exec('git ' . $arguments . ' 2>' . (PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null'), $output, $status);
    /** @var list<string> $output */

    return $status === 0 ? implode("
", $output) : '';
}

function normalisePath(string $path): string
{
    $real = realpath($path);
    $path = str_replace('\\', '/', $real === false ? $path : $real);

    return PHP_OS_FAMILY === 'Windows' ? strtolower($path) : $path;
}

/**
 * @param array<array-key, mixed> $issue
 */
function formatIssue(array $issue, bool $snippet): string
{
    $line = sprintf(
        '%s %s:%d:%d %s: %s',
        strtoupper(($issue['is_security'] ?? false) === true ? 'security' : (string) ($issue['severity'] ?? '')),
        (string) ($issue['file_name'] ?? ''),
        (int) ($issue['line_from'] ?? 0),
        (int) ($issue['column_from'] ?? 0),
        (string) ($issue['type'] ?? ''),
        (string) ($issue['message'] ?? ''),
    );
    if ($snippet && isset($issue['snippet']) && is_string($issue['snippet'])) {
        $line .= "\n    " . str_replace("\n", "\n    ", trim($issue['snippet'])) . "\n";
    }

    return $line;
}

/**
 * @param array<array-key, int> $counts
 */
function printCounts(string $title, array $counts, int $limit): void
{
    arsort($counts);
    echo $title, "\n";
    $width = 0;
    foreach (array_keys(array_slice($counts, 0, $limit, true)) as $name) {
        $width = max($width, strlen((string) $name));
    }
    foreach (array_slice($counts, 0, $limit, true) as $name => $count) {
        printf("  %-{$width}s %5d\n", $name, $count);
    }
    if (count($counts) > $limit) {
        printf("  … %d more (--limit)\n", count($counts) - $limit);
    }
}

/** @var list<string> $argv */
$options = parseArguments($argv);
$issues = readReport($options['report']);

if (!$options['info']) {
    $issues = array_filter($issues, static fn(array $i): bool => ($i['severity'] ?? '') !== 'info');
}
if ($options['types'] !== []) {
    $issues = array_filter($issues, static fn(array $i): bool => in_array($i['type'] ?? '', $options['types'], true));
}
if ($options['file'] !== null) {
    $needle = $options['file'];
    $issues = array_filter(
        $issues,
        static fn(array $i): bool => str_contains(str_replace('\\', '/', (string) ($i['file_name'] ?? '')), $needle),
    );
}
if ($options['changed'] !== null) {
    $changed = changedLines($options['changed']);
    $issues = array_filter($issues, static function (array $i) use ($changed): bool {
        $lines = $changed[normalisePath((string) ($i['file_path'] ?? ''))] ?? [];
        if ($lines === true) {
            return true;
        }
        for ($l = (int) ($i['line_from'] ?? 0); $l <= (int) ($i['line_to'] ?? 0); $l++) {
            if (isset($lines[$l])) {
                return true;
            }
        }

        return false;
    });
}
$issues = array_values($issues);

if ($issues === []) {
    echo "No matching issues.\n";
    exit(0);
}

$filtered = $options['types'] !== [] || $options['file'] !== null || $options['changed'] !== null;
if ($filtered) {
    foreach (array_slice($issues, 0, $options['limit']) as $issue) {
        echo formatIssue($issue, $options['snippets']), "\n";
    }
    if (count($issues) > $options['limit']) {
        printf("… %d more (--limit)\n", count($issues) - $options['limit']);
    }
    exit(0);
}

$byType = [];
$byFile = [];
$security = 0;
foreach ($issues as $issue) {
    $type = (string) ($issue['type'] ?? '?');
    if (($issue['is_security'] ?? false) === true) {
        $security++;
        $type .= ' (security)';
    }
    $byType[$type] = ($byType[$type] ?? 0) + 1;
    $file = (string) ($issue['file_name'] ?? '?');
    $byFile[$file] = ($byFile[$file] ?? 0) + 1;
}
printf("%d issues (%d security) in %d files\n\n", count($issues), $security, count($byFile));
printCounts('By type:', $byType, $options['limit']);
echo "\n";
printCounts('By file:', $byFile, min(10, $options['limit']));
