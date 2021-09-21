# TaintedCookie

Potential cookie injection. This rule is emitted when user-controlled input can be passed into a cookie.

## Risk

The risk of setting arbitrary cookies depends on further application configuration. 

Examples of potential issues:

- Session Fixation: If the authentication cookie doesn't change after a successful login an attacker could fixate the session cookie. If a victim logs in with a fixated cookie, the attacker can now take over the session of the user.
- Cross-Site-Scripting (XSS): Some application code could read cookies and print it out unsanitized to the user.



## Example

```php
<?php

setcookie('authtoken', $_GET['value'], time() + (86400 * 30), '/');
```

## Mitigations

If this is required functionality, limit the cookie setting to values and not the name. (e.g. `authtoken` in the example)

Make sure to change session tokens after authentication attempts.

## Further resources

- [OWASP Wiki for Session fixation](https://owasp.org/www-community/attacks/Session_fixation)
- [Session Management Cheatsheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
- [CWE-384](https://cwe.mitre.org/data/definitions/384.html)
