# TaintedLdap

Potential LDAP injection. This rule is emitted when user-controlled input can be passed into an LDAP request.

## Risk

Passing untrusted user input to LDAP requests could be dangerous. 

If LDAP requests like these are used for login purposes, it could result in an authentication bypass. An attacker could write a filter that would evaluate to `true` for any user, and thus bruteforce credentials easily. 


## Example

```php
<?php

$ds = ldap_connect('example.com');
$dn = 'o=Psalm, c=US';
$filter = $_GET['filter'];
ldap_search($ds, $dn, $filter, []);
```

## Mitigations

Use [`ldap_escape`](https://www.php.net/manual/en/function.ldap-escape.php) to escape user input to the LDAP filter and DN.


## Further resources

- [OWASP Wiki for LDAP Injections](https://owasp.org/www-community/attacks/LDAP_Injection)
- [LDAP Injection Prevention Cheatsheet](https://cheatsheetseries.owasp.org/cheatsheets/LDAP_Injection_Prevention_Cheat_Sheet.html)
- [CWE-90](https://cwe.mitre.org/data/definitions/90.html)
