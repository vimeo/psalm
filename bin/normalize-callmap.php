<?php

declare(strict_types=1);

require __DIR__ . '/gen_callmap_utils.php';

foreach (glob(__DIR__."/../dictionaries/CallMap*.php") as $file) {
    $callMap = require $file;
    $callMap = normalizeCallMap($callMap);
    writeCallMap($file, $callMap);
}
