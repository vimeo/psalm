# ImpureGlobalVariable

Emitted when attempting to use a global or superglobal variable from a function or method marked as pure

```php
<?php

/** @psalm-pure */
function getFromSuperglobal() : int {
    return (int) $_GET['v'];
}

/** @psalm-pure */
function addCumulativeGlobal(int $left) : int {
    /** @var int */
    global $i;
    $i ??= 0;
    $i += $left;
    return $left;
}

/** @psalm-pure */
function addCumulativeGlobals(int $left) : int {
    $GLOBALS['i'] ??= 0;
    $GLOBALS['i'] += $left;
    return $left;
}
```
