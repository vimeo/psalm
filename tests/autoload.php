<?php

declare(strict_types=1);

use DG\BypassFinals;

require __DIR__ . '/../vendor/autoload.php';

BypassFinals::enable();
BypassFinals::denyPaths(['*tests/fixtures/DummyProject*']);

// Psalm's config reads the paths to check from the command line (see CliUtils::getPathsToCheck()).
// The command line of a ParaTest worker holds ParaTest's own options, with paths to files it hasn't
// written yet: drop them, as the worker has read them already.
if (getenv('PARATEST') !== false && isset($GLOBALS['argv']) && is_array($GLOBALS['argv'])) {
    $GLOBALS['argv'] = array_slice($GLOBALS['argv'], 0, 1);
    $_SERVER['argv'] = $GLOBALS['argv'];
}
