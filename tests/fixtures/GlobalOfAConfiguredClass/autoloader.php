<?php

// the classes are only found through the autoloader: nothing in file.php names them
spl_autoload_register(static function (string $class): void {
    $files = [
        'GlobalOfAConfiguredClass\\Connection' => __DIR__ . '/lib/Connection.php',
        'GlobalOfAConfiguredClass\\Pool' => __DIR__ . '/lib/Pool.php',
    ];

    if (isset($files[$class])) {
        require $files[$class];
    }
});
