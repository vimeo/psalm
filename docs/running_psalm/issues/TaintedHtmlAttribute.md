# TaintedHtmlAttribute

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input is placed in an HTML attribute value without quotes. HTML escaping
(`htmlspecialchars()`, `htmlentities()`) leaves spaces, and a space ends an unquoted value, so the input can add
attributes of its own.

## Example

```php
<?php

$src = (string) $_GET['src'];

echo '<img src=' . htmlspecialchars($src, ENT_QUOTES) . '>'; // TaintedHtmlAttribute: `x onerror=alert(1)` adds an event handler
echo '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '">'; // no issue
echo '<img src=' . rawurlencode($src) . '>'; // no issue: URL encoding leaves no spaces
```

Psalm sees where the value is from the markup before it in the same expression (a concatenation, an interpolated
string or a `sprintf()` format). It doesn't check a template such as `<img src=<?= htmlspecialchars($src) ?>>`.

## Mitigations

- Put the attribute value in quotes, and escape it with `ENT_QUOTES`.

## Further resources

- [OWASP Cross Site Scripting Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [CWE-79](https://cwe.mitre.org/data/definitions/79.html)
