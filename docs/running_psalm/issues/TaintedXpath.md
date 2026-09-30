# TaintedXpath

This is a **security issue**, reported by [security analysis](../../security_analysis/index.md): it flags a potential vulnerability rather than a type error or a code-quality problem.

Emitted when user-controlled input can be passed into a xpath query.

```php
<?php

function queryExpression(SimpleXMLElement $xml) : array|false|null {
    $expression = $_GET["expression"];
    return $xml->xpath($expression);
}
```
