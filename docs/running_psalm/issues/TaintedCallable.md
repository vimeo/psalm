# TaintedCallable

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability rather than a type error or a code-quality problem.

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
