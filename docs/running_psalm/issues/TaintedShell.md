# TaintedShell

Emitted when user-controlled input can be passed into to an `exec` call or similar.

```php
<?php

$command = $_GET["command"];

runCode($command);

function runCode(string $command) {
    exec($command);
}
```
