<?php

declare(strict_types=1);

use DG\BypassFinals;

require __DIR__ . '/../vendor/autoload.php';

// Rewriting a file is slow (token_get_all() on each one that loads, the CallMaps included), so only the final
// classes the tests extend or mock are rewritten.
BypassFinals::enable();
BypassFinals::allowPaths(array_map(
    static fn(string $class_path): string => '*/src/Psalm/' . $class_path . '.php',
    [
        'Codebase',
        'Config',
        'Internal/Analyzer/ProjectAnalyzer',
        'Internal/Codebase/Analyzer',
        'Internal/EventDispatcher',
        'Internal/PluginManager/ComposerLock',
        'Internal/PluginManager/ConfigFile',
        'Internal/PluginManager/PluginList',
        'Internal/PluginManager/PluginListFactory',
        'Internal/Provider/FileReferenceCacheProvider',
        'Internal/Provider/ParserCacheProvider',
    ],
));

// Psalm's config reads the paths to check from the command line (see CliUtils::getPathsToCheck()).
// The command line of a ParaTest worker holds ParaTest's own options, with paths to files it hasn't
// written yet: drop them, as the worker has read them already.
if (getenv('PARATEST') !== false && isset($GLOBALS['argv']) && is_array($GLOBALS['argv'])) {
    $GLOBALS['argv'] = array_slice($GLOBALS['argv'], 0, 1);
    $_SERVER['argv'] = $GLOBALS['argv'];
}
