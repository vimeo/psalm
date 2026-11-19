# TaintedUserSecret

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when tainted input detection is turned on and data marked as a user secret is detected somewhere it shouldn’t be.

## Risk

A secret of the user (a password, a token, personal data) that reaches output, a log or a response is disclosed to whoever can read it.

## Example

```php
<?php

class User {
    /**
     * @psalm-taint-source user_secret
     */
    public function getPassword() : string {
        return "$omePa$$word";
    }
}

function showUserPassword(User $user) {
    echo $user->getPassword();
}
```

## Mitigations

- Don't print or log the value; if it must leave the system, redact it first.

## Further resources

- [CWE-200](https://cwe.mitre.org/data/definitions/200.html)
