# TaintedEval

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input can be passed into an `eval` call.

## Risk

`eval` runs a string as PHP code, so user input that reaches it is remote code execution.

Passing untrusted user input to `eval` calls is dangerous, as it allows arbitrary data to be executed on your server.

```php
<?php

$name = $_GET["name"];

evalCode($name);

function evalCode(string $name) {
    eval($name);
}
```

## Mitigations

- Don't use `eval`; use data formats (JSON) or an allowlist of operations instead.
- If it cannot be avoided, never put user input in the evaluated string.

## Further resources

- [OWASP Wiki for Code Injection](https://owasp.org/www-community/attacks/Code_Injection)
- [CWE-95](https://cwe.mitre.org/data/definitions/95.html)
