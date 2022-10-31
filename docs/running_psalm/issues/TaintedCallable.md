# TaintedCallable

Emitted when tainted text is used in an arbitrary function call.

This can lead to dangerous situations, like running arbitrary functions.

```php
<?php

$name = $_GET["name"];

evalCode($name);

function evalCode(string $name) {
    if (is_callable($name)) {
        $name();
    }
}
```
