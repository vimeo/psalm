<?php

declare(strict_types=1);

require __DIR__ . '/gen_callmap_utils.php';

$callMap = require "dictionaries/CallMap.php";
$orig = $callMap;

foreach ($callMap as $functionName => &$entry) {
    $refl = getReflectionFunction($functionName);
    if (!$refl) {
        continue;
    }
    assertEntryParameters($refl, $entry);
} unset($entry);

writeCallMap("dictionaries/CallMap.php", $callMap);

$diffFile = "dictionaries/CallMap_84_delta.php";

$diff = require $diffFile;

foreach ($callMap as $functionName => $entry) {
    if ($orig[$functionName] !== $entry) {
        $diff['changed'][$functionName]['old'] = $orig[$functionName];
        $diff['changed'][$functionName]['new'] = $entry;
    }
}

writeCallMap($diffFile, $diff);
