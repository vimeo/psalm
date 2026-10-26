<?php

declare(strict_types=1);

// Adds up the summaries of the shards of the mutation testing job, and fails when the MSI of the whole source tree is
// below the minMsi of infection.json5. The MSI is Infection's: mutants skipped or ignored don't count.

if ($argc < 2) {
    fwrite(STDERR, "Usage: $argv[0] <summary.json>...\n");
    exit(1);
}

$config = json_decode(
    preg_replace('~^\s*//.*$~m', '', file_get_contents(dirname(__DIR__, 2) . '/infection.json5')),
    true,
    flags: JSON_THROW_ON_ERROR,
);
$min_msi = (float) ($config['minMsi'] ?? 0);

$totals = [];
foreach (array_slice($argv, 1) as $path) {
    $stats = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR)['stats'];
    foreach ($stats as $key => $count) {
        if (str_ends_with($key, 'Count')) {
            $totals[$key] = ($totals[$key] ?? 0) + $count;
        }
    }
}

$detected = $totals['killedCount'] + $totals['errorCount'] + $totals['syntaxErrorCount'] + $totals['timeOutCount'];
$tested = $totals['totalMutantsCount'] - $totals['skippedCount'] - $totals['ignoredCount'];
$msi = $tested === 0 ? 0.0 : round(100 * $detected / $tested, 2);

foreach ($totals as $key => $count) {
    printf("%-20s %d\n", $key, $count);
}
printf("\nMSI: %.2f%% (minimum: %.2f%%)\n", $msi, $min_msi);

if ($msi < $min_msi) {
    fwrite(STDERR, "The MSI is below the minMsi of infection.json5\n");
    exit(1);
}
