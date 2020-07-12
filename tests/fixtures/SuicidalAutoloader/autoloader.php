<?php

use React\Promise\PromiseInterface as ReactPromise;

spl_autoload_register(function (string $className) {
    $knownBadClasses = [
        ReactPromise::class, // amphp/amp
        ResourceBundle::class, // symfony/polyfill-php73
        Transliterator::class, // symfony/string
        // it's unclear why Psalm tries to autoload parent
        'parent',
    ];

    if (in_array($className, $knownBadClasses)) {
        return;
    }

    $ex = new RuntimeException('Attempted to load ' . $className);
    echo $ex->__toString() . "\n\n" . $ex->getTraceAsString() . "\n\n";
    exit(70);
});
