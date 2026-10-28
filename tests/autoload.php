<?php

declare(strict_types=1);

use DG\BypassFinals;
use DG\BypassFinals\MutatingWrapper;
use DG\BypassFinals\NativeWrapper;
use Psalm\Tests\BypassFinalsUnderlyingWrapper;

require __DIR__ . '/../vendor/autoload.php';

BypassFinals::enable();
BypassFinals::denyPaths(['*tests/fixtures/DummyProject*']);

// Infection's mutant runs register an include interceptor, which BypassFinals then reads through, but which registers
// itself again after each operation, dropping BypassFinals' wrapper
if (MutatingWrapper::$underlyingWrapperClass !== NativeWrapper::class) {
    BypassFinalsUnderlyingWrapper::$wrapperClass = MutatingWrapper::$underlyingWrapperClass;
    MutatingWrapper::$underlyingWrapperClass = BypassFinalsUnderlyingWrapper::class;
    // loading BypassFinalsUnderlyingWrapper went through the interceptor already
    stream_wrapper_unregister('file');
    stream_wrapper_register('file', MutatingWrapper::class);
}

// Psalm's config reads the paths to check from the command line (see CliUtils::getPathsToCheck()).
// The command line of a ParaTest worker holds ParaTest's own options, with paths to files it hasn't
// written yet: drop them, as the worker has read them already.
if (getenv('PARATEST') !== false && isset($GLOBALS['argv']) && is_array($GLOBALS['argv'])) {
    $GLOBALS['argv'] = array_slice($GLOBALS['argv'], 0, 1);
    $_SERVER['argv'] = $GLOBALS['argv'];
}
