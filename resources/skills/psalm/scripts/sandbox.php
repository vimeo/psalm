<?php

declare(strict_types=1);

const USAGE = <<<'TXT'
Usage: php sandbox.php FILE.php [--php=8.3] [--types] [--settings=JSON]

Checks one self-contained file on https://psalm.dev (the latest development version of Psalm) and prints
the issues. The file leaves the machine: use it for a reduced repro only.

  --php=X.Y        PHP version of the analysis (default: the sandbox's)
  --types          also print the type Psalm inferred for each expression
  --settings=JSON  sandbox options, e.g. {"unused_variables":true,"check_throws":true}

TXT;

function fail(string $message): never
{
    fwrite(STDERR, rtrim($message) . "\n");
    exit(1);
}

function lineOf(string $code, int $offset): int
{
    return substr_count($code, "\n", 0, min($offset, strlen($code))) + 1;
}

/** @var list<string> $argv */
$file = null;
$php = null;
$types = false;
$settings = '{}';
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '-h' || $arg === '--help') {
        fwrite(STDOUT, USAGE);
        exit(0);
    } elseif (preg_match('/^--php=(\d+\.\d+)$/', $arg, $m)) {
        $php = $m[1];
    } elseif ($arg === '--types') {
        $types = true;
    } elseif (preg_match('/^--settings=(.+)$/s', $arg, $m)) {
        if (!is_array(json_decode($m[1], true))) {
            fail('--settings must be a JSON object');
        }
        $settings = $m[1];
    } elseif ($arg !== '' && $arg[0] !== '-') {
        $file = $arg;
    } else {
        fail("Unknown option $arg\n\n" . USAGE);
    }
}
if ($file === null) {
    fail(USAGE);
}

$code = @file_get_contents($file);
if ($code === false) {
    fail("Cannot read $file");
}

$context = stream_context_create(['http' => [
    'method' => 'POST',
    'header' => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: psalm-agent-skill\r\n",
    'content' => http_build_query(['code' => $code, 'settings' => $settings] + ($php === null ? [] : ['php' => $php])),
    'timeout' => 60,
    'ignore_errors' => true,
]]);
$body = @file_get_contents('https://psalm.dev/check', false, $context);
if ($body === false) {
    fail('psalm.dev did not answer');
}
$response = json_decode($body, true);
if (!is_array($response)) {
    fail("Unexpected answer from psalm.dev:\n" . substr($body, 0, 500));
}
if (isset($response['error'])) {
    fail('psalm.dev: ' . (string) json_encode($response['error']));
}

printf("Psalm %s, PHP %s\n", (string) ($response['version'] ?? '?'), (string) ($response['php_version'] ?? $php ?? '?'));

$results = isset($response['results']) && is_array($response['results']) ? $response['results'] : [];
if ($results === []) {
    echo "No issues.\n";
}
foreach ($results as $issue) {
    if (!is_array($issue)) {
        continue;
    }
    printf(
        "%s %d:%d %s: %s\n",
        ($issue['is_security'] ?? false) === true ? 'SECURITY' : strtoupper((string) ($issue['severity'] ?? '')),
        (int) ($issue['line_from'] ?? 0),
        (int) ($issue['column_from'] ?? 0),
        (string) ($issue['type'] ?? ''),
        (string) ($issue['message'] ?? ''),
    );
}

$found = array_filter($results, static fn(mixed $i): bool => is_array($i) && ($i['severity'] ?? '') !== 'info');

if ($types) {
    echo "\nInferred types:\n";
    $typeMap = isset($response['type_map']) && is_array($response['type_map']) ? $response['type_map'] : [];
    foreach ($typeMap as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $from = (int) ($entry['from'] ?? 0);
        $to = (int) ($entry['to'] ?? 0);
        $expression = preg_replace('/\s+/', ' ', substr($code, $from, $to - $from));
        printf("  %d: %s : %s\n", lineOf($code, $from), (string) $expression, (string) ($entry['type'] ?? ''));
    }
}

exit($found === [] ? 0 : 2);
