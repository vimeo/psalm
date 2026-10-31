# TaintedCallable

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when tainted text is used in an arbitrary function call.

## Risk

An attacker who controls the function name can run any function that is callable from there, with arguments of their choice, which usually ends in code execution.

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

## Mitigations

- Don't build the name of the function from user input: map known values to functions with an allowlist (`$handlers[$input]`).
- If the input must select a function, check it against a fixed list before the call.

## Further resources

- [OWASP Wiki for Unsafe use of Reflection](https://owasp.org/www-community/vulnerabilities/Unsafe_use_of_Reflection)
- [CWE-470](https://cwe.mitre.org/data/definitions/470.html)
