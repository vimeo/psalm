# TaintedSleep

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input can be passed into a `sleep` call or similar.

## Risk

A user-controlled delay lets an attacker keep workers busy for as long as they ask, which is a cheap denial of service.

## Example

```php
<?php

sleep($_GET["seconds"]);
```

The options making curl throttle a transfer (`CURLOPT_MAX_RECV_SPEED_LARGE`, ...) or wait longer for it (`CURLOPT_TIMEOUT`, ...) are reported too: a client choosing them can keep the request busy as long as it wants.

## Mitigations

- Cap the value (`min($seconds, 5)`) before it reaches `sleep`.
- Validate it against the range your feature needs.

## Further resources

- [OWASP Wiki for Denial of Service](https://owasp.org/www-community/attacks/Denial_of_Service)
- [CWE-400](https://cwe.mitre.org/data/definitions/400.html)
