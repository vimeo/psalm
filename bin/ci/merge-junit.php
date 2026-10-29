<?php

/**
 * Merges the JUnit reports of the coverage chunks into one, for Infection.
 */

declare(strict_types=1);

if (count($argv) < 3) {
    fwrite(STDERR, 'Usage: ' . $argv[0] . ' <output> <report>...' . PHP_EOL);
    exit(1);
}

$merged = new DOMDocument('1.0', 'UTF-8');
$root = $merged->appendChild($merged->createElement('testsuites'));

foreach (array_slice($argv, 2) as $report_path) {
    $report = new DOMDocument();
    if (!$report->load($report_path)) {
        fwrite(STDERR, 'Cannot read ' . $report_path . PHP_EOL);
        exit(1);
    }

    foreach ($report->documentElement->childNodes as $suite) {
        if ($suite instanceof DOMElement) {
            $root->appendChild($merged->importNode($suite, true));
        }
    }
}

// Infection takes the time of a test case to be that of its whole class, and skips a mutant whose tests' classes take
// longer than its timeout, though it then runs only the cases covering the mutant: the time of a class becomes that of
// its slowest case, so that the mutants of the lines the cases of a long class cover are tested instead of skipped
foreach ((new DOMXPath($merged))->query('//testsuite[@file][not(contains(@name, "::"))]') ?: [] as $class_suite) {
    assert($class_suite instanceof DOMElement);
    $slowest = 0.0;
    foreach ($class_suite->getElementsByTagName('testcase') as $test_case) {
        $slowest = max($slowest, (float) $test_case->getAttribute('time'));
    }
    $class_suite->setAttribute('time', (string) $slowest);
}

$merged->formatOutput = true;
$merged->save($argv[1]);
