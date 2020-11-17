# TaintedSSRF

Potential Server-Side Request Forgery vulnerability. This rule is emitted when user-controlled input can be passed into a network request.

## Risk

Passing untrusted user input to network requests could be dangerous. 

If an attacker can fully control a HTTP request they could connect to internal services. Depending on the nature of these, this can pose a security risk. (e.g. backend services, admin interfaces, AWS metadata, ...)

## Example

```php
<?php
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $_GET['url']);

curl_exec($ch);

curl_close($ch);
```

## Mitigations

Mitigating SSRF vulnerabilities can be tricky. Disallowing IPs would likely not work as an attacker could create a malicious domain that points to an internal DNS name.

Consider:

1. Having an allow list of domains that can be connected to.
2. Pointing cURL to a proxy that has no access to internal resources.

## Further resources

- [OWASP Wiki for Server Side Request Forgery](https://owasp.org/www-community/attacks/Server_Side_Request_Forgery)
- [CWE-918](https://cwe.mitre.org/data/definitions/918)