# TaintedXpath

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input can be passed into a xpath query.

## Risk

User input in an XPath query lets an attacker change the query and read nodes of the document they shouldn't, much like SQL injection.

## Example

```php
<?php

function queryExpression(SimpleXMLElement $xml) : array|false|null {
    $expression = $_GET["expression"];
    return $xml->xpath($expression);
}
```

## Mitigations

- Don't build the query from user input; compare against a value read from the document.
- Allowlist the characters the input may contain, and reject quotes.

## Further resources

- [OWASP Wiki for XPath Injection](https://owasp.org/www-community/attacks/XPATH_Injection)
- [CWE-643](https://cwe.mitre.org/data/definitions/643.html)
