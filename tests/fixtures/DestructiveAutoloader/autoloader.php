<?php

// attempt to destroy some variables Psalm entrypoint may be using
// this will run when autoloader is being registered
foreach ($GLOBALS as $key => $_) {
    if ($key === 'GLOBALS') {
        continue;
    }
    $GLOBALS[$key] = new Exception;
}

spl_autoload_register(static function () {
    // and destroy vars again
    // this will run during scanning (?)
    foreach ($GLOBALS as $key => $_) {
        if ($key === 'GLOBALS') {
            continue;
        }
        $GLOBALS[$key] = new Exception;
    }
});
