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

$merged->formatOutput = true;
$merged->save($argv[1]);
