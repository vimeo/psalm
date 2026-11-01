<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols


declare(strict_types=1);

/** Perf CI toolbox (plan, config, normalize, compare). See bin/perf/README.md. */

const USAGE = <<<'TXT'
usage: php perf.php plan --corpus <file>
       php perf.php config --corpus <file> --project <name> [--no-library-plugins]
       php perf.php normalize <report.json> <project root> <out.issues.json>
       php perf.php compare --plan <plan.json> --in <dir> --base-sha <sha> --head-sha <sha> --deps-changed 0|1
                            --out-md <file> --out-json <file> [--run-url <url>]

TXT;

/**
 * Parses `--name value` and `--name=value` (getopt() stops at the subcommand word).
 *
 * @param list<string> $args
 * @param list<string> $names accepted option names
 * @return array<string, string>
 * @throws RuntimeException on unknown options, missing values or stray arguments
 */
function parseOptions(array $args, array $names): array
{
    $options = [];
    for ($i = 0, $n = count($args); $i < $n; $i++) {
        if (preg_match('/^--([a-z0-9-]+)(?:=(.*))?$/s', $args[$i], $m) !== 1) {
            throw new RuntimeException("unexpected argument '{$args[$i]}'");
        }

        if (!in_array($m[1], $names, true)) {
            throw new RuntimeException("unknown option --{$m[1]}");
        }

        if (isset($m[2])) {
            $options[$m[1]] = $m[2];
        } elseif (isset($args[$i + 1])) {
            $options[$m[1]] = $args[++$i];
        } else {
            throw new RuntimeException("option --{$m[1]} needs a value");
        }
    }

    return $options;
}

/**
 * @param array<string, string> $options
 * @param list<string> $names
 * @throws RuntimeException
 */
function requireOptions(array $options, array $names): void
{
    foreach ($names as $name) {
        if (($options[$name] ?? '') === '') {
            throw new RuntimeException("missing --{$name}");
        }
    }
}

/**
 * @return array<mixed>
 * @throws RuntimeException
 */
function readJsonFile(string $path): array
{
    $raw = is_file($path) ? file_get_contents($path) : false;
    if ($raw === false) {
        throw new RuntimeException("cannot read {$path}");
    }

    try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new RuntimeException("{$path} is not valid JSON: {$e->getMessage()}");
    }

    if (!is_array($data)) {
        throw new RuntimeException("{$path} does not hold a JSON object or array");
    }

    return $data;
}

/**
 * @param array<mixed> $data
 * @throws RuntimeException
 */
function writeJsonFile(string $path, array $data, bool $pretty = true): void
{
    $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        | ($pretty ? JSON_PRETTY_PRINT : 0);
    $json = json_encode($data, $flags);
    if ($json === false || file_put_contents($path, $json . "\n") === false) {
        throw new RuntimeException("cannot write {$path}");
    }
}

require __DIR__ . '/lib/plan.php';
require __DIR__ . '/lib/config.php';
require __DIR__ . '/lib/normalize.php';
require __DIR__ . '/lib/compare.php';
require __DIR__ . '/lib/report.php';

$command = $argv[1] ?? '';
$handlers = ['plan' => 'runPlan', 'config' => 'runConfig', 'normalize' => 'runNormalize', 'compare' => 'runCompare'];
if (!isset($handlers[$command])) {
    fwrite(STDERR, USAGE);
    exit(2);
}

try {
    exit($handlers[$command](array_slice($argv, 2)));
} catch (RuntimeException $e) {
    fwrite(STDERR, "perf {$command}: {$e->getMessage()}\n");
    exit(1);
}
