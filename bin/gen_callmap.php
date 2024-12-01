<?php

declare(strict_types=1);

// Written by SamMousa in https://github.com/vimeo/psalm/issues/8101, finalized by @danog

require 'vendor/autoload.php';

require __DIR__ . '/gen_callmap_utils.php';

use DG\BypassFinals;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\TestConfig;

BypassFinals::enable();

function writeCallMap(string $file, array $callMap): void
{
    file_put_contents($file, '<?php // phpcs:ignoreFile

return '.var_export($callMap, true).';');
}

new ProjectAnalyzer(new TestConfig, new Providers(new FileProvider));
$callMap = require "dictionaries/CallMap.php";
$orig = $callMap;

$codebase = ProjectAnalyzer::getInstance()->getCodebase();

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
