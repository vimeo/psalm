# TaintedXpath

Emitted when user-controlled input can be passed into to a xpath query.

```php
<?php

function queryExpression(SimpleXMLElement $xml) : array|false|null {
    $expression = $_GET["expression"];
    return $xml->xpath($expression);
}
```
