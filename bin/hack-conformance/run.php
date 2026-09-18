<?php

/**
 * Hack conformance runner for Psalm's class-template type-variable feature.
 *
 * For each fixture in ./fixtures/*.hack it runs the pinned HHVM typechecker
 * (via docker) in an isolated Hack project and checks the verdict (errors vs
 * no errors) against the fixture's declared `//// expect:` header, which mirrors
 * the linked Psalm test. A mismatch means Hack's behaviour has drifted from what
 * the Psalm feature encodes — investigate before trusting the Psalm test.
 *
 * Usage:   php bin/hack-conformance/run.php [--image <ref>] [--verbose]
 * Requires: docker. See README.md.
 *
 * This is opt-in tooling, not part of the PHPUnit suite (HHVM/docker only).
 */

declare(strict_types=1);

$root = __DIR__;
$manifest = json_decode((string) file_get_contents("$root/manifest.json"), true);
$image = $manifest['hhvm_image'] ?? 'hhvm/hhvm:latest';
$verbose = false;

foreach (array_slice($argv, 1) as $i => $arg) {
    if ($arg === '--verbose') {
        $verbose = true;
    } elseif ($arg === '--image') {
        $image = $argv[$i + 2] ?? $image;
    }
}

$fixtures = glob("$root/fixtures/*.hack") ?: [];
if ($fixtures === []) {
    fwrite(STDERR, "No fixtures found in $root/fixtures\n");
    exit(2);
}

// Parse each fixture's `//// expect:` and `//// psalm-test:` headers.
$expected = [];
foreach ($fixtures as $path) {
    $name = basename($path);
    $src = (string) file_get_contents($path);
    preg_match('/^\/\/\/\/\s*expect:\s*(\S+)/m', $src, $e);
    preg_match('/^\/\/\/\/\s*psalm-test:\s*(.+)$/m', $src, $t);
    $expect = $e[1] ?? '';
    if ($expect !== 'no-errors' && $expect !== 'error') {
        fwrite(STDERR, "Fixture $name has no valid `//// expect:` header (no-errors|error)\n");
        exit(2);
    }
    $expected[$name] = [
        'expect' => $expect,
        'psalm_test' => trim($t[1] ?? '(unlinked)'),
    ];
}

// Ensure the image is present.
exec('docker image inspect ' . escapeshellarg($image) . ' >/dev/null 2>&1', $_o, $present);
if ($present !== 0) {
    fwrite(STDERR, "Pulling $image ...\n");
    passthru('docker pull ' . escapeshellarg($image), $pull);
    if ($pull !== 0) {
        fwrite(STDERR, "Failed to pull $image\n");
        exit(2);
    }
}

// One container run; inside, typecheck each fixture in its own project (the
// fixtures reuse class names, so they cannot share a single Hack project).
$inner = <<<'SH'
set -e
for f in /fixtures/*.hack; do
  name=$(basename "$f")
  dir=$(mktemp -d)
  : > "$dir/.hhconfig"
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
exec($cmd . ' 2>&1', $out, $rc);
$output = implode("\n", $out);

// Split the combined output back into per-fixture blocks.
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

$fail = 0;
$pad = max(array_map('strlen', array_keys($expected)));
printf("HHVM image: %s\n\n", $image);
foreach ($expected as $name => $meta) {
    $lines = $blocks[$name] ?? null;
    if ($lines === null) {
        printf("  %-{$pad}s  ??  no HHVM output (runner error)\n", $name);
        $fail++;
        continue;
    }
    $body = trim(implode("\n", $lines));
    $hasErrors = $body !== '' && stripos($body, 'No errors') === false;
    $actual = $hasErrors ? 'error' : 'no-errors';
    $ok = $actual === $meta['expect'];
    printf(
        "  %s %-{$pad}s  expect=%-9s hhvm=%-9s  -> %s\n",
        $ok ? 'ok  ' : 'FAIL',
        $name,
        $meta['expect'],
        $actual,
        $meta['psalm_test'],
    );
    if (!$ok) {
        $fail++;
    }
    if ($verbose && $body !== '') {
        foreach (explode("\n", $body) as $bl) {
            printf("        | %s\n", $bl);
        }
    }
}

printf("\n%d fixture(s), %d mismatch(es)\n", count($expected), $fail);
exit($fail === 0 ? 0 : 1);
