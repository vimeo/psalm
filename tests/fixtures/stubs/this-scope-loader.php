<?php

use X\Y;

/**
 * @param string $class_name
 * @return void
 */
function autoload_xy($class_name) {
    if ($class_name === Y::class) {
        spl_autoload_unregister(__FUNCTION__);

        require __DIR__ . DIRECTORY_SEPARATOR . 'this-scope-xy.php';
    }
}

spl_autoload_register('autoload_xy');
