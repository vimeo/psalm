# TaintedHeader

Potential header injection. This rule is emitted when user-controlled input can be passed into a HTTP header.

## Risk

The risk of a header injection depends hugely on your environment.

If your webserver supports something like [`XSendFile`](https://www.nginx.com/resources/wiki/start/topics/examples/xsendfile/) / [`X-Accel`](https://www.nginx.com/resources/wiki/start/topics/examples/x-accel/), an attacker could potentially access arbitrary files on the systems.

If your system does not do that, there may be other concerns, such as:

- Cookie Injection
- Open Redirects
- Proxy Cache Poisoning

## Example

```php
<?php

header($_GET['header']);
```

## Mitigations

Make sure only the value and not the key can be set by an attacker. (e.g. `header('Location: ' . $_GET['target']);`)

Verify the set values are sensible. Consider using an allow list. (e.g. for redirections)

## Further resources

- [Unvalidated Redirects and Forwards Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.html)
- [OWASP Wiki for Cache Poisoning](https://owasp.org/www-community/attacks/Cache_Poisoning)
- [CWE-601](https://cwe.mitre.org/data/definitions/601.html)
- [CWE-644](https://cwe.mitre.org/data/definitions/644.html)
