# TaintedHtml

Emitted when user-controlled input that can contain HTML can be passed into to an `echo` statement.

## Risk

This could lead to a potential Cross Site Scripting (XSS) vulnerability. Using a XSS vulnerability, an attacker could inject malicious JavaScript and execute any action JavaScript could do. Examples include:

- Stealing authentication material (e.g. cookies, JWT tokens)
- Exfiltrate sensitive information by reading the DOM
- Keylog entries on the website (e.g. fake login form)

Whether this is exploitable or not depends on a few conditions:

- Is an executable mimetype set? (e.g. `text/html`)
- Is the content served inline or as attachment? (`Content-Disposition`)
- Is the output properly sanitized? (e.g. stripping all HTML tags or having an allowlist of allowed characters)

## Example

```php
<?php

$name = $_GET["name"];

printName($name);

function printName(string $name) {
    echo $name;
}
```

## Mitigations

- Sanitize user-input by using functions such as `htmlentities` or use an allowlist.
- Set all cookies to `HTTPOnly`.
- Consider using Content Security Policy (CSP), to limit the risk of XSS vulnerabilities.
- If user input itself is HTML, see [Sanitizing HTML User Input](../../security_analysis/avoiding_false_positives.md#sanitizing-html-user-input)

## Further resources

- [OWASP Wiki for Cross Site Scripting (XSS)](https://owasp.org/www-community/attacks/xss/)
- [Content-Security-Policy - Web Fundamentals](https://developers.google.com/web/fundamentals/security/csp)
