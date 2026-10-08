<?php

/**
 * Shared helpers for the Hack conformance harness (CLI only; lives under bin/,
 * which Psalm does not analyse, so it may use exec/docker freely). The PHPUnit
 * integration in tests/ shells out to run.php --json rather than requiring this.
 */

declare(strict_types=1);

/** @return array<string, mixed> */
function hc_manifest(string $root): array
{
    $decoded = json_decode((string) file_get_contents("$root/manifest.json"), true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Every fixture under fixtures/ (one subfolder per topic), keyed by its path
 * relative to fixtures/, with its headers parsed:
 *
 *   //// expect: no-errors|error       what HHVM (and Psalm, on the transpiled code) reports
 *   //// psalm-error: <IssueType>      for `expect: error`, the issue Psalm reports first
 *   //// psalm-ignore: <IssueType>, ...  issues Psalm must not report on the transpiled code
 *   //// psalm-divergence: <text>       Psalm does not agree with HHVM yet, and why: the
 *                                       transpiled case is skipped
 *   //// hhconfig: <key> = <value>     a line of the fixture's .hhconfig (repeatable)
 *   //// note: <text>                  what the fixture checks
 *
 * Indented `////` lines continue the header above them.
 *
 * @return array<string, array{
 *     expect: string,
 *     psalm_error: ?string,
 *     psalm_ignore: list<string>,
 *     psalm_divergence: ?string,
 *     note: string,
 *     path: string,
 * }>
 */
function hc_fixtures(string $root): array
{
    $out = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator("$root/fixtures", FilesystemIterator::SKIP_DOTS),
    );
    foreach ($files as $file) {
        $path = (string) $file;
        if (!str_ends_with($path, '.hack')) {
            continue;
        }
        $name = substr($path, strlen("$root/fixtures/"));
        $headers = [];
        $last = null;
        foreach (explode("\n", (string) file_get_contents($path)) as $line) {
            if (preg_match('#^//// ([a-z-]+):\s*(.*)$#', $line, $m)) {
                $last = $m[1];
                $headers[$last][] = $m[2];
            } elseif ($last !== null && preg_match('#^////\s+(.*)$#', $line, $m)) {
                $headers[$last][count($headers[$last]) - 1] .= "\n" . $m[1];
            } else {
                $last = null;
            }
        }
        $expect = $headers['expect'][0] ?? '';
        if ($expect !== 'no-errors' && $expect !== 'error') {
            fwrite(STDERR, "Fixture $name has no valid `//// expect:` header (no-errors|error)\n");
            exit(2);
        }
        $psalmError = $headers['psalm-error'][0] ?? null;
        if (($expect === 'error') !== ($psalmError !== null)) {
            fwrite(STDERR, "Fixture $name needs a `//// psalm-error:` header exactly when it expects an error\n");
            exit(2);
        }
        $ignore = [];
        foreach ($headers['psalm-ignore'] ?? [] as $issues) {
            $ignore = [...$ignore, ...preg_split('/[\s,]+/', $issues, -1, PREG_SPLIT_NO_EMPTY)];
        }
        $out[$name] = [
            'expect' => $expect,
            'psalm_error' => $psalmError,
            'psalm_ignore' => $ignore,
            'psalm_divergence' => isset($headers['psalm-divergence'])
                ? implode("\n", $headers['psalm-divergence'])
                : null,
            'note' => implode("\n", $headers['note'] ?? []),
            'path' => $path,
        ];
    }
    ksort($out);
    return $out;
}

/**
 * The name of a fixture's case in the transpiled Psalm suite (the traits of the
 * suite skip the cases whose name has `SKIPPED-`).
 *
 * @param array<string, mixed> $fixture an entry of hc_fixtures()
 */
function hc_case_name(string $name, array $fixture): string
{
    return ($fixture['psalm_divergence'] !== null ? 'SKIPPED-' : '') . substr($name, 0, -strlen('.hack'));
}

/**
 * Returns null when the harness can run, or a human reason why it cannot
 * (used to skip the PHPUnit test cleanly).
 */
function hc_unavailable_reason(string $image, bool $allow_pull): ?string
{
    if (stripos(PHP_OS, 'WIN') === 0) {
        return 'Hack conformance needs a Linux HHVM container';
    }

    exec('command -v docker 2>/dev/null', $_o, $rc);
    if ($rc !== 0) {
        return 'docker is not installed';
    }

    exec('docker info >/dev/null 2>&1', $_o2, $rcInfo);
    if ($rcInfo !== 0) {
        return 'docker daemon is not reachable';
    }

    exec('docker image inspect ' . escapeshellarg($image) . ' >/dev/null 2>&1', $_o3, $rcImg);
    if ($rcImg !== 0) {
        if (!$allow_pull) {
            return "HHVM image not present locally ($image); run bin/hack-conformance/run.php to pull it";
        }
        fwrite(STDERR, "Pulling $image ...\n");
        passthru('docker pull ' . escapeshellarg($image), $rcPull);
        if ($rcPull !== 0) {
            return "failed to pull $image";
        }
    }

    return null;
}

/**
 * Typecheck every fixture in its own isolated Hack project (fixtures reuse class
 * names, so they cannot share one project) in a single container invocation.
 * A fixture may carry `//// hhconfig: <key> = <value>` header lines; each is
 * written verbatim into that fixture's `.hhconfig`.
 *
 * @param array<string, array<string, mixed>> $expected fixtures, as hc_fixtures() returns them
 * @return array<string, array{expect: string, actual: string, psalm_case: string, output: string}>
 */
function hc_run(string $root, string $image, array $expected): array
{
    $inner = <<<'SH'
set -e
for f in $(cd /fixtures && find . -name '*.hack' | sed 's#^\./##' | sort); do
  name=$f
  f=/fixtures/$f
  dir=$(mktemp -d)
  # Per-fixture typechecker options: every `//// hhconfig: key = value` header
  # line becomes a line of the project's .hhconfig (e.g. union type hints).
  sed -nE 's#^////[[:space:]]*hhconfig:[[:space:]]*##p' "$f" > "$dir/.hhconfig"
  cp "$f" "$dir/test.hack"
  echo "@@@BEGIN@@@ $name"
  (cd "$dir" && hh_client --no-load 2>&1 | grep -vE 'daemon|launched|hh_server|waiting-client|Running in') || true
  echo "@@@END@@@ $name"
  rm -rf "$dir"
done
SH;

    $cmd = 'docker run --rm '
        . '-v ' . escapeshellarg("$root/fixtures") . ':/fixtures:ro '
        . escapeshellarg($image) . ' bash -c ' . escapeshellarg($inner);

    $out = [];
    exec($cmd . ' 2>&1', $out);
    $output = implode("\n", $out);

    // Split combined output into per-fixture blocks.
    $blocks = [];
    $current = null;
    foreach (explode("\n", $output) as $line) {
        if (preg_match('/^@@@BEGIN@@@ (.+)$/', $line, $m)) {
            $current = trim($m[1]);
            $blocks[$current] = [];
        } elseif (preg_match('/^@@@END@@@ /', $line)) {
            $current = null;
        } elseif ($current !== null) {
            $blocks[$current][] = $line;
        }
    }

    $results = [];
    foreach ($expected as $name => $meta) {
        $body = isset($blocks[$name]) ? trim(implode("\n", $blocks[$name])) : '';
        $missing = !isset($blocks[$name]);
        $hasErrors = $body !== '' && stripos($body, 'No errors') === false;
        $results[$name] = [
            'expect' => $meta['expect'],
            'actual' => $missing ? 'missing' : ($hasErrors ? 'error' : 'no-errors'),
            'psalm_case' => 'HackConformanceTranspiledTest::' . hc_case_name($name, $meta),
            'output' => $body,
        ];
    }

    return $results;
}

/**
 * HHVM's parse tree of every fixture (`hh_parse --full-fidelity-json-parse-tree`),
 * keyed like hc_fixtures(), which Transpiler turns into PHP.
 *
 * Trees are cached in .hh-parse-cache/ by image and file contents, so HHVM only
 * runs (in one container) for fixtures changed since the last run. Returns a
 * string, the reason, when an uncached fixture needs HHVM and it cannot run here.
 *
 * @param array<string, array<string, mixed>> $fixtures as hc_fixtures() returns them
 * @return array<string, array<string, mixed>>|string
 */
function hc_parse_trees(string $root, string $image, array $fixtures, bool $allow_pull): array|string
{
    $cache = "$root/.hh-parse-cache";
    $paths = [];
    $missing = [];
    foreach ($fixtures as $name => $fixture) {
        $paths[$name] = "$cache/" . sha1($image . "\0" . file_get_contents($fixture['path'])) . '.json';
        if (!is_file($paths[$name])) {
            $missing[] = $name;
        }
    }

    if ($missing !== []) {
        $reason = hc_unavailable_reason($image, $allow_pull);
        if ($reason !== null) {
            return $reason;
        }

        $inner = <<<'SH'
set -e
for f in "$@"; do
  echo "@@@BEGIN@@@ $f"
  hh_parse --full-fidelity-json-parse-tree "/fixtures/$f"
  echo
  echo "@@@END@@@ $f"
done
SH;
        $cmd = 'docker run --rm '
            . '-v ' . escapeshellarg("$root/fixtures") . ':/fixtures:ro '
            . escapeshellarg($image) . ' bash -c ' . escapeshellarg($inner) . ' hh_parse '
            . implode(' ', array_map('escapeshellarg', $missing));
        $out = [];
        exec($cmd . ' 2>/dev/null', $out, $rc);
        if ($rc !== 0) {
            throw new UnexpectedValueException("hh_parse failed (exit $rc)");
        }

        $blocks = [];
        $current = null;
        foreach ($out as $line) {
            if (preg_match('/^@@@BEGIN@@@ (.+)$/', $line, $m)) {
                $current = $m[1];
                $blocks[$current] = '';
            } elseif (preg_match('/^@@@END@@@ /', $line)) {
                $current = null;
            } elseif ($current !== null) {
                $blocks[$current] .= $line . "\n";
            }
        }

        @mkdir($cache, 0777, true);
        foreach ($missing as $name) {
            if (!is_array(json_decode($blocks[$name] ?? '', true))) {
                throw new UnexpectedValueException("hh_parse printed no parse tree for $name");
            }
            file_put_contents($paths[$name], $blocks[$name]);
        }
    }

    $trees = [];
    foreach ($paths as $name => $path) {
        $tree = json_decode((string) file_get_contents($path), true);
        if (!is_array($tree)) {
            throw new UnexpectedValueException("Corrupt cached parse tree $path: delete it");
        }
        $trees[$name] = $tree;
    }
    return $trees;
}
