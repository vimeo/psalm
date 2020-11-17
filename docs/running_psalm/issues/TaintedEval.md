# TaintedEval

Emitted when user-controlled input can be passed into to an `eval` call.

Passing untrusted user input to `eval` calls is dangerous, as it allows arbitrary data to be executed on your server.

```php
<?php

$name = $_GET["name"];

evalCode($name);

function evalCode(string $name) {
    eval($name);
}
```
