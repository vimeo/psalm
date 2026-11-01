# TaintedSystemSecret

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when data marked as a system secret is detected somewhere it shouldn’t be.

## Risk

A secret of the system (a key, a password, a token) that reaches output, a log or a response is disclosed to whoever can read it.

## Example

```php
<?php

/**
 * @psalm-taint-source system_secret
 */
function getConfigValue(string $data) {
    return "$omePa$$word";
}

echo getConfigValue("secret");
```

## Mitigations

- Don't print or log the secret; if it must leave the system, redact it first.
- Keep secrets in a secrets store or environment, not in values that flow to output.

## Further resources

- [Secrets Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html)
- [CWE-200](https://cwe.mitre.org/data/definitions/200.html)
