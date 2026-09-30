# TaintedEval

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability rather than a type error or a code-quality problem.

Emitted when user-controlled input can be passed into an `eval` call.

Passing untrusted user input to `eval` calls is dangerous, as it allows arbitrary data to be executed on your server.

```php
<?php

$name = $_GET["name"];

evalCode($name);

function evalCode(string $name) {
    eval($name);
}
```
