# TaintedUrlComponent

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input is placed, without being URL-encoded, in the path, the query or the fragment of a URL whose server is fixed, and the URL is then requested.

The input can't choose the server the request is sent to (that would be a [TaintedSSRF](TaintedSSRF.md)), but it can still change the request in ways it shouldn't, with URL syntax: `../` to reach another path of the server, `&` to add query parameters or override earlier ones, `#` to cut off the rest of the URL.

## Example

```php
<?php
$query = (string) $_GET['query'];

// with $query = "a&admin=1", this sends ?q=a&admin=1
file_get_contents('https://api.example.com/search?q=' . $query);
```

## Mitigations

Encode the input with `rawurlencode()` or `urlencode()`, or build the query string with `http_build_query()`:

```php
<?php
$query = (string) $_GET['query'];

file_get_contents('https://api.example.com/search?q=' . rawurlencode($query));
file_get_contents('https://api.example.com/search?' . http_build_query(['q' => $query]));
```

In the path of the URL, an encoded value can still be a `..` segment: see [TaintedUrlPath](TaintedUrlPath.md).

## Further resources

- [OWASP Testing Guide: HTTP Parameter Pollution](https://owasp.org/www-project-web-security-testing-guide/latest/4-Web_Application_Security_Testing/07-Input_Validation_Testing/04-Testing_for_HTTP_Parameter_Pollution)
- [CWE-235](https://cwe.mitre.org/data/definitions/235)
