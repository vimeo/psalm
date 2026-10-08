# TaintedShell

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input can be passed into an `exec` call or similar.

```php
<?php

$command = $_GET["command"];

runCode($command);

function runCode(string $command) {
    exec($command);
}
```
