# TaintedInclude

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability rather than a type error or a code-quality problem.

Emitted when user-controlled input can be passed into an `include` or `require` expression.

Passing untrusted user input to `include` calls is dangerous, as it can allow an attacker to execute arbitrary scripts on your server.

```php
<?php

$name = $_GET["name"];

includeCode($name);

function includeCode(string $name) : void {
    include($name . '.php');
}
```
