# TaintedUnserialize

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Tainted input detected to an `unserialize` call.

## Risk

`unserialize` on user input lets an attacker create objects of any loaded class, and the magic methods of those objects (`__wakeup`, `__destruct`, …) can lead to code execution (PHP object injection).

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

## Mitigations

- Use a format that carries only data, such as `json_decode`.
- If you must unserialize, pass `['allowed_classes' => false]` or a list of the classes you expect, and sign the payload.

## Further resources

- [OWASP Wiki for PHP Object Injection](https://owasp.org/www-community/vulnerabilities/PHP_Object_Injection)
- [Deserialization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Deserialization_Cheat_Sheet.html)
- [CWE-502](https://cwe.mitre.org/data/definitions/502.html)
