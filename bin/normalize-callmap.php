<?php

declare(strict_types=1);

use Webmozart\Assert\Assert;

require __DIR__ . '/gen_callmap_utils.php';

$baseMaps = [];

foreach (glob(__DIR__."/../dictionaries/base/CallMap_*.php") as $file) {
    Assert::eq(preg_match('/_(\d+)\.php/', $file, $matches), 1);
    $version = $matches[1];

    $baseMaps[$version] = normalizeCallMap(require $file);
}

ksort($baseMaps);
$last = array_key_last($baseMaps);

$customMaps = [
    $last => normalizeCallMap(require __DIR__."/../dictionaries/override/CallMap.php")
];

$diffs = [];
foreach (glob(__DIR__."/../dictionaries/override/CallMap_*.php") as $file) {
    Assert::eq(preg_match('/_(\d+)_delta\.php/', $file, $matches), 1);
    $version = $matches[1];
    $diffs[$version] = normalizeCallMap(require $file);
}
krsort($diffs);

$versions = array_reverse(array_keys($diffs));

foreach ($diffs as $version => $diff) {
    $callMap = $customMaps[$version];
    $diff = normalizeCallMap(require $file);
    foreach ($diff['removed'] as $func => $descr) {
        $callMap[$func] = $descr;
    }
    foreach ($diff['added'] as $func => $descr) {
        unset($callMap[$func]);
    }
    foreach ($diff['changed'] as $func => $sub) {
        $callMap[$func] = $sub['old'];
    }

    $prevVersion = array_search($version, $versions)-1;
    if ($prevVersion < 0) {
        continue;
    }
    $customMaps[$versions[$prevVersion]] = $callMap;
}

foreach ($customMaps as $version => $data) {
    foreach ($data as $name => $func) {
        if (($baseMaps[$version][$name] ?? null) === $func) {
            unset($customMaps[$version][$name]);
        } else if(($baseMaps[$version][$name] ?? null))
        var_dump($name, ($baseMaps[$version][$name] ?? null), $func);
    }
}

var_dump($customMaps);