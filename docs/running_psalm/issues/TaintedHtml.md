# TaintedHtml

Emitted when user-controlled input can be passed into to an `echo` statement.

```php
<?php

$name = $_GET["name"];

printName($name);

function printName(string $name) {
    echo $name;
}
```
