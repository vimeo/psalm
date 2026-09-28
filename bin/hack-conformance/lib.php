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
 * Parse each fixture's `//// expect:` and `//// psalm-test:` headers.
 *
 * @return array<string, array{expect: string, psalm_test: string, path: string}>
 */
function hc_fixtures(string $root): array
{
    $out = [];
    foreach (glob("$root/fixtures/*.hack") ?: [] as $path) {
        $name = basename($path);
        $src = (string) file_get_contents($path);
        preg_match('/^\/\/\/\/\s*expect:\s*(\S+)/m', $src, $e);
        preg_match('/^\/\/\/\/\s*psalm-test:\s*(.+)$/m', $src, $t);
        $expect = $e[1] ?? '';
        if ($expect !== 'no-errors' && $expect !== 'error') {
            fwrite(STDERR, "Fixture $name has no valid `//// expect:` header (no-errors|error)\n");
            exit(2);
        }
        $out[$name] = [
            'expect' => $expect,
            'psalm_test' => trim($t[1] ?? '(unlinked)'),
            'path' => $path,
        ];
    }
    ksort($out);
    return $out;
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
 * @param array<string, array{expect: string, psalm_test: string, path: string}> $expected
 * @return array<string, array{expect: string, actual: string, psalm_test: string, output: string}>
 */
function hc_run(string $root, string $image, array $expected): array
{
    $inner = <<<'SH'
set -e
for f in /fixtures/*.hack; do
  name=$(basename "$f")
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
            'psalm_test' => $meta['psalm_test'],
            'output' => $body,
        ];
    }

    return $results;
}
