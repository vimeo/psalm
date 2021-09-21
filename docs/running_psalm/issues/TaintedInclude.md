# TaintedInclude

Emitted when user-controlled input can be passed into to an `include` or `require` expression.

Passing untrusted user input to `include` calls is dangerous, as it can allow an attacker to execute arbitrary scripts on your server.

```php
<?php

$name = $_GET["name"];

includeCode($name);

function includeCode(string $name) : void {
    include($name . '.php');
}
```
