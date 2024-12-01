<?php

declare(strict_types=1);

$callmap = [];

require __DIR__ . '/gen_callmap_utils.php';

foreach (get_defined_functions()['internal'] as $name) {
    $func = new ReflectionFunction($name);

    $args = paramsToEntries($func);

    $callmap[$name] = $args;
}

foreach (get_declared_classes() as $class) {
    $refl = new ReflectionClass($class);
    if (!$refl->isInternal()) {
        continue;
    }

    foreach ($refl->getMethods() as $method) {
        $args = paramsToEntries($method);
    
        $callmap[$class.'::'.$method->getName()] = $args;
    }
}

$callmap = normalizeCallMap($callmap);
writeCallMap(__DIR__.'/../dictionaries/CallMap.php', $callmap);