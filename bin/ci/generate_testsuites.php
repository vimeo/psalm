<?php

/**
 * Writes phpunit.xml with one testsuite per CI runner, chunk_1 to chunk_<number_of_chunks>, so that
 * every runner takes about as long.
 *
 * Each test file is weighted by how long its tests took (bin/ci/test_times.json, refreshed with
 * bin/ci/update_test_times.php); a file not timed yet counts as a median one. Files go to chunks
 * longest first, each to the chunk with the least work so far. A runner then runs its chunk with
 * paratest in functional mode, which spreads even the longest file over all of its cores.
 */

declare(strict_types=1);

$number_of_chunks = count($argv) === 2 ? (int) $argv[1] : 0;
if ($number_of_chunks <= 0) {
    fwrite(STDERR, 'Usage: ' . $argv[0] . ' <number_of_chunks>' . PHP_EOL);
    exit(1);
}

$root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;

/** @var array<string, int|float> $times */
$times = json_decode((string) file_get_contents(__DIR__ . '/test_times.json'), true, 512, JSON_THROW_ON_ERROR);

// find tests -name '*Test.php'
$files = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . 'tests', FilesystemIterator::SKIP_DOTS),
);
foreach ($iterator as $file) {
    $path = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($root)));
    if (str_ends_with($path, 'Test.php')) {
        $files[$path] = 0;
    }
}

$known = array_values($times);
sort($known);
$default_time = $known === [] ? 1 : $known[intdiv(count($known), 2)];
foreach ($files as $path => $_) {
    $files[$path] = $times[$path] ?? $default_time;
}

// longest first; ties by name, so every runner computes the same chunks
uksort(
    $files,
    static fn(string $a, string $b): int => [$files[$b], $a] <=> [$files[$a], $b],
);

$loads = array_fill(1, $number_of_chunks, 0);
$chunks = array_fill(1, $number_of_chunks, []);
foreach ($files as $path => $time) {
    $chunk = array_keys($loads, min($loads))[0];
    $loads[$chunk] += $time;
    $chunks[$chunk][] = $path;
}

$phpunit_config = new DOMDocument('1.0', 'UTF-8');
$phpunit_config->preserveWhiteSpace = false;
$phpunit_config->load($root . 'phpunit.xml.dist');
$suites_container = $phpunit_config->getElementsByTagName('testsuites')->item(0);

while ($suites_container->firstChild) {
    $suites_container->removeChild($suites_container->firstChild);
}

foreach ($chunks as $chunk_id => $chunk) {
    $suite = $phpunit_config->createElement('testsuite');
    $suite->setAttribute('name', 'chunk_' . $chunk_id);
    foreach ($chunk as $file) {
        $suite->appendChild($phpunit_config->createElement('file', $root . $file));
    }
    $suites_container->appendChild($suite);
}

$phpunit_config->formatOutput = true;
$phpunit_config->save($root . 'phpunit.xml');
