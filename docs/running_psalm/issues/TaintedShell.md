# TaintedShell

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability rather than a type error or a code-quality problem.

Emitted when user-controlled input can be passed into an `exec` call or similar.

```php
<?php

$command = $_GET["command"];

runCode($command);

function runCode(string $command) {
    exec($command);
}
```
