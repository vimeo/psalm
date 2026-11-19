<?php

/**
 * Refreshes bin/ci/test_times.json, the seconds each test file takes, which generate_testsuites.php
 * balances the CI runners with.
 *
 * Usage: php bin/ci/update_test_times.php <junit.xml>...
 *
 * Takes the JUnit logs of a run of the whole suite, such as the test-times artifacts the unit test
 * workflow uploads (one per runner). Files missing from the logs keep their time; files that no
 * longer exist are dropped.
 */

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, 'Usage: ' . $argv[0] . ' <junit.xml>...' . PHP_EOL);
    exit(1);
}

$root = dirname(__DIR__, 2);
$times_file = __DIR__ . '/test_times.json';

/** @var array<string, int|float> $times */
$times = json_decode((string) file_get_contents($times_file), true, 512, JSON_THROW_ON_ERROR);

$measured = [];
foreach (array_slice($argv, 1) as $log) {
    $document = new DOMDocument();
    if (!$document->load($log)) {
        fwrite(STDERR, 'Could not read ' . $log . PHP_EOL);
        exit(1);
    }

    foreach ($document->getElementsByTagName('testsuite') as $suite) {
        $file = str_replace('\\', '/', $suite->getAttribute('file'));
        // the suite of a test class, not one of its data providers or the suites wrapping them
        if (!str_ends_with($file, 'Test.php') || str_contains($suite->getAttribute('name'), '::')) {
            continue;
        }

        $position = strrpos($file, '/tests/');
        if ($position === false) {
            continue;
        }

        $path = substr($file, $position + 1);
        $measured[$path] = ($measured[$path] ?? 0) + (float) $suite->getAttribute('time');
    }
}

foreach ($measured as $path => $time) {
    $times[$path] = max(1, (int) round($time));
}

foreach (array_keys($times) as $path) {
    if (!is_file($root . '/' . $path)) {
        unset($times[$path]);
    }
}

ksort($times);
file_put_contents($times_file, json_encode($times, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

echo 'Updated ', count($measured), ' of ', count($times), ' test files', PHP_EOL;
