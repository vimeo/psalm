# TaintedShell

Emitted when tainted input detection is turned on and tainted shell code is detected.

```php
<?php

$command = $_GET["command"];

runCode($command);

function runCode(string $command) {
    exec($command);
}
```
