<?php

ini_set('display_startup_errors', 1);
ini_set('html_errors', 1);
error_reporting(-1);

foreach ([__DIR__ . '/../../../autoload.php', __DIR__ . '/../vendor/autoload.php'] as $file) {
    if (file_exists($file)) {
        require $file;
        break;
    }
}
