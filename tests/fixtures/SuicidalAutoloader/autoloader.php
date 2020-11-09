<?php

use React\Promise\PromiseInterface as ReactPromise;
use Composer\InstalledVersions;

spl_autoload_register(function (string $className) {
    $knownBadClasses = [
        ReactPromise::class, // amphp/amp
        ResourceBundle::class, // symfony/polyfill-php73
        Transliterator::class, // symfony/string
        InstalledVersions::class, // composer v2
        // it's unclear why Psalm tries to autoload parent
        'parent',
        'PHPUnit\Framework\ArrayAccess',
        'PHPUnit\Framework\Countable',
        'PHPUnit\Framework\DOMDocument',
        'PHPUnit\Framework\DOMElement',
        'Stringable',
    ];

    if (in_array($className, $knownBadClasses)) {
        return;
    }

    $ex = new RuntimeException('Attempted to load ' . $className);
    echo $ex->__toString() . "\n\n" . $ex->getTraceAsString() . "\n\n";
    exit(70);
});
