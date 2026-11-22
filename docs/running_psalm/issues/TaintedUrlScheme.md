# TaintedUrlScheme

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input starts a URL that a browser runs as script with the `javascript:` scheme: the
`href` of a link (`<a>`, `<area>`), the `action` of a form, the `formaction` of a button, or the `src` of a frame.
HTML escaping leaves the `:` of `javascript:alert(1)`, so the code runs when the link is clicked, the form is sent or
the frame loads.

Psalm doesn't report other attributes: browsers don't run a `javascript:` URL in the `src` of an image, a script or a
stylesheet.

## Example

```php
<?php

$url = (string) $_GET['url'];

echo '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '">Profile</a>'; // TaintedUrlScheme
echo '<a href="/users/' . htmlspecialchars($url, ENT_QUOTES) . '">Profile</a>'; // no issue: the URL starts with a path
echo '<img src="' . htmlspecialchars($url, ENT_QUOTES) . '">'; // no issue: images don't run `javascript:` URLs

if (preg_match('~^https?://~', $url)) {
    echo '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '">Profile</a>'; // no issue
}
```

Psalm sees where the value is from the markup before it in the same expression (a concatenation, an interpolated
string or a `sprintf()` format). It doesn't check a template such as `<a href="<?= htmlspecialchars($url) ?>">`.

## Mitigations

- Start the URL with a literal path or origin: `'/users/' . rawurlencode($name)`, `'https://example.com/' . ...`.
- Check the scheme before output, with `str_starts_with($url, 'https://')` or `preg_match('~^https?://~', $url)`.
  Psalm understands both. `filter_var($url, FILTER_VALIDATE_URL)` isn't enough: it accepts
  `javascript://comment%0Aalert(1)`.
- If Psalm can't see your check (for example, comparing `parse_url($url, PHP_URL_SCHEME)` with a list), put it in a
  function that returns the URL, and annotate the function with `@psalm-taint-escape url_scheme`.

## Further resources

- [OWASP Cross Site Scripting Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [CWE-79](https://cwe.mitre.org/data/definitions/79.html)
