# TaintedCallable

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when tainted text names the function, method or class that the code calls or instantiates.

## Risk

An attacker who controls the function or method name can run any function or method that is callable from there, with arguments of their choice, which usually ends in code execution.

This can lead to dangerous situations, like running arbitrary functions.

```php
<?php

$name = $_GET["name"];

evalCode($name);

function evalCode(string $name) {
    if (is_callable($name)) {
        $name();
    }
}
```

A method name works the same way, whether it names a method of an object or of a class:

```php
<?php

final class Account {
    public function delete(): void {}
}

$action = (string) $_GET["action"];
(new Account())->$action(); // TaintedCallable
[new Account(), $action](); // TaintedCallable

if ($action === "delete") {
    (new Account())->$action(); // no issue
}
```

## Mitigations

- Don't build the name of the function or method from user input: map known values to functions with an allowlist (`$handlers[$input]`).
- If the input must select a function or method, check it against a fixed list before the call.

## Further resources

- [OWASP Wiki for Unsafe use of Reflection](https://owasp.org/www-community/vulnerabilities/Unsafe_use_of_Reflection)
- [CWE-470](https://cwe.mitre.org/data/definitions/470.html)
