# TaintedUnserialize

Tainted input detected to an `unserialize` call.

Passing untrusted user input to `unserialize` calls is dangerous – from the [PHP documentation on unserialize](https://www.php.net/manual/en/function.unserialize.php):

> Do not pass untrusted user input to unserialize() regardless of the options value of allowed_classes. Unserialization can result in code being loaded and executed due to object instantiation and autoloading, and a malicious user may be able to exploit this. Use a safe, standard data interchange format such as JSON (via json_decode() and json_encode()) if you need to pass serialized data to the user.

```php
<?php

$command = $_GET["data"];

getObject($command);

function getObject(string $data) : object {
    return unserialize($data);
}
```
