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
 * Usage:   php bin/hack-conformance/run.php [--image <ref>] [--verbose] [--json]
 * Requires: docker. See README.md.
 *
 * `--json` prints machine-readable results and never pulls the image; it is what
 * tests/HackConformanceTest.php consumes so the harness is part of the unit
 * suite (skipping cleanly when docker/HHVM is unavailable).
 *
 * This is opt-in tooling, not part of the PHPUnit suite by itself (HHVM/docker).
 */

declare(strict_types=1);

require __DIR__ . '/lib.php';

$root = __DIR__;
$manifest = hc_manifest($root);
$image = is_string($manifest['hhvm_image'] ?? null) ? $manifest['hhvm_image'] : 'hhvm/hhvm:latest';
$verbose = false;
$json = false;

$args = array_slice($argv, 1);
foreach ($args as $i => $arg) {
    if ($arg === '--verbose') {
        $verbose = true;
    } elseif ($arg === '--json') {
        $json = true;
    } elseif ($arg === '--image') {
        $next = $args[$i + 1] ?? null;
        if (is_string($next)) {
            $image = $next;
        }
    }
}

$expected = hc_fixtures($root);
if ($expected === []) {
    fwrite(STDERR, "No fixtures found in $root/fixtures\n");
    exit(2);
}

$reason = hc_unavailable_reason($image, allow_pull: !$json);

if ($json) {
    if ($reason !== null) {
        echo json_encode(['available' => false, 'reason' => $reason], JSON_PRETTY_PRINT), "\n";
        exit(0);
    }
    $results = hc_run($root, $image, $expected);
    echo json_encode(['available' => true, 'image' => $image, 'results' => $results], JSON_PRETTY_PRINT), "\n";
    exit(0);
}

if ($reason !== null) {
    fwrite(STDERR, "Cannot run: $reason\n");
    exit(2);
}

$results = hc_run($root, $image, $expected);

$fail = 0;
$pad = max(array_map('strlen', array_keys($expected)));
printf("HHVM image: %s\n\n", $image);
foreach ($results as $name => $r) {
    $ok = $r['actual'] === $r['expect'];
    printf(
        "  %s %-{$pad}s  expect=%-9s hhvm=%-9s  -> %s\n",
        $ok ? 'ok  ' : 'FAIL',
        $name,
        $r['expect'],
        $r['actual'],
        $r['psalm_test'],
    );
    if (!$ok) {
        $fail++;
    }
    if ($verbose && $r['output'] !== '') {
        foreach (explode("\n", $r['output']) as $bl) {
            printf("        | %s\n", $bl);
        }
    }
}

printf("\n%d fixture(s), %d mismatch(es)\n", count($results), $fail);
exit($fail === 0 ? 0 : 1);
