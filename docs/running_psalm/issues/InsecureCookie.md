# InsecureCookie

This is a **security issue**: it flags a potential vulnerability.

Emitted when `setcookie()` or `setrawcookie()` set a cookie without the `secure` flag: it isn't given, or is `false`.

```php
<?php

function remember(string $session): void {
    setcookie('session', $session); // InsecureCookie
    setcookie('session', $session, ['httponly' => true]); // InsecureCookie
    setcookie('session', $session, ['secure' => true, 'httponly' => true]); // no issue
}
```

## Why this is bad

The browser then also sends the cookie over plain HTTP, where anyone on the network can read it, and steal a session.

## How to fix

Pass `secure: true`, or `'secure' => true` in the options.

## Further resources

- [CWE-614](https://cwe.mitre.org/data/definitions/614.html)
