# TaintedHtml

Emitted when tainted input detection is turned on and tainted HTML is detected.

```php
<?php

$name = $_GET["name"];

printName($name);

function printName(string $name) {
    echo $name;
}
```
