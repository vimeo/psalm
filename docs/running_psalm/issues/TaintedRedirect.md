# TaintedRedirect

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input can choose where a redirect sends the client (open redirect).

## Risk

A link to the application that redirects to a site of the attacker's choice looks trustworthy: it is used for phishing, and to steal the tokens some flows (OAuth, password reset) append to the redirect target.

## Example

```php
<?php

header('Location: ' . $_GET['next']); // TaintedRedirect
header('Location: /' . $_GET['next']); // TaintedRedirect: `//evil.example` is another server
header('Location: https://example.com' . $_GET['next']); // TaintedRedirect: `.evil.example` is another server
header('Location: https://example.com/' . $_GET['page']); // TaintedUrlComponent: the server is fixed, but `?admin=1#` changes the query
header('Location: /docs/' . rawurlencode((string) $_GET['page'])); // TaintedUrlPath: `..` reaches another path
header('Location: /account?tab=' . urlencode((string) $_GET['tab'])); // no issue
```

## Mitigations

- Redirect to a path of the application, or to a URL whose server is fixed, rather than to a URL from the request, and URL-encode what goes in it (see [TaintedUrlComponent](TaintedUrlComponent.md)).
- Check the target against an allowlist of servers or paths.

## Further resources

- [Unvalidated Redirects and Forwards Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.html)
- [CWE-601](https://cwe.mitre.org/data/definitions/601.html)
