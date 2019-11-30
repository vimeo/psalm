<?php
spl_autoload_register(function (string $className) {
    $ex = new RuntimeException('Attempted to load ' . $className);
    echo $ex->__toString() . "\n\n" . $ex->getTraceAsString() . "\n\n";
    exit(70);
});
