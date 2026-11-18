# TaintedSession

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input is stored in the session (trust boundary violation).

## Risk

The code that reads the session back usually trusts what it finds there, as if the application had put it there itself: a user name or a role taken from the request lets the client pick it.

## Example

```php
<?php

$_SESSION['user'] = $_GET['user']; // TaintedSession
$_SESSION['page'] = (int) $_GET['page']; // no issue
```

## Mitigations

- Store what the application decided (the id of the authenticated user), not what the request says.
- Validate the input before storing it, and annotate the function doing so with `@psalm-taint-escape session`.

## Further resources

- [CWE-501](https://cwe.mitre.org/data/definitions/501.html)
