<?php

ini_set('display_startup_errors', 1);
ini_set('html_errors', 1);
ini_set('memory_limit', '-1');
error_reporting(E_ALL);

foreach ([__DIR__ . '/../../../autoload.php', __DIR__ . '/../vendor/autoload.php'] as $file) {
    if (file_exists($file)) {
        /** @psalm-suppress UnresolvableInclude */
        require $file;
        break;
    }
}
