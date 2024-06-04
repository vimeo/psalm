# TaintedExtract

Emitted when user-controlled input can be passed into the `$_SESSION` array.

## Example

```php
<?php

$usrname = $_GET["usrname"];
if (!isset($_SESSION["attr_user"])) {
    $_SESSION["attr_user"] = $usrname;
}
```

## Further ressource

[CWE-501: Trust Boundary Violation](https://cwe.mitre.org/data/definitions/501.html)
