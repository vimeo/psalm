# TaintedUrlPath

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input is placed in the path of a URL whose server is fixed, and the URL is then requested, even once URL-encoded: URL encoding leaves `.` as it is, so the input can still be a `..` segment of the path.

HTTP clients such as cURL resolve these segments before sending the request, so the input can reach another path of the server than the one intended (client-side path traversal).

## Example

```php
<?php
$id = rawurlencode((string) $_GET['id']);

// with $id = "..", this requests https://api.example.com/profile
file_get_contents('https://api.example.com/users/' . $id . '/profile');
```

## Mitigations

Only accept the values the path segment can have, e.g. cast an identifier to `int` or format it with `sprintf('%d')`, or validate it in a function annotated with `@psalm-taint-escape url_path`.

Values placed in the query or the fragment of the URL (after a `?` or a `#`) can't change its path, and aren't reported.

## Further resources

- [OWASP: Path Traversal](https://owasp.org/www-community/attacks/Path_Traversal)
- [CWE-22](https://cwe.mitre.org/data/definitions/22)
