# TaintedHeader

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Potential header injection. This rule is emitted when user-controlled input can be passed into an HTTP header.

## Risk

The risk of a header injection depends hugely on your environment.

If your webserver supports something like [`XSendFile`](https://www.nginx.com/resources/wiki/start/topics/examples/xsendfile/) / [`X-Accel`](https://www.nginx.com/resources/wiki/start/topics/examples/x-accel/), an attacker could potentially access arbitrary files on the systems.

If your system does not do that, there may be other concerns, such as:

- Cookie Injection
- Open Redirects
- Proxy Cache Poisoning

The headers of a request the application makes itself are sinks too. Unlike `header()`, curl writes the value of an option such as `CURLOPT_HTTPHEADER`, `CURLOPT_USERAGENT`, `CURLOPT_COOKIE` or `CURLOPT_CUSTOMREQUEST` into the request as is. A line break in it adds headers to the request, for example another `Host` or `Authorization` header. Given to `CURLOPT_CUSTOMREQUEST` or `CURLOPT_REQUEST_TARGET`, it adds a whole other request to the connection. `CURLOPT_QUOTE` sends the FTP or SFTP commands it's given as they are, and `CURLOPT_COOKIELIST` adds cookies to the requests.

## Example

```php
<?php

header($_GET['header']);
```

```php
<?php

$ch = curl_init('https://api.example.com/');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Locale: ' . $_GET['locale']]);
curl_exec($ch);
```

## Mitigations

Make sure only the value and not the key can be set by an attacker. (e.g. `header('Location: ' . $_GET['target']);`)

Verify the set values are sensible. Consider using an allow list. (e.g. for redirections)

For the headers of a request made with curl, reject values containing `\r` or `\n`, and annotate the function doing so with `@psalm-taint-escape header`.

## Further resources

- [Unvalidated Redirects and Forwards Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.html)
- [OWASP Wiki for Cache Poisoning](https://owasp.org/www-community/attacks/Cache_Poisoning)
- [CWE-601](https://cwe.mitre.org/data/definitions/601.html)
- [CWE-644](https://cwe.mitre.org/data/definitions/644.html)
