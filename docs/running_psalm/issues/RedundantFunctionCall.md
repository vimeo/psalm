# RedundantFunctionCall

Emitted when a function call (`array_values`, `strtolower`, `ksort` etc.) is redundant.

```php
<?php

$a = ['already', 'a', 'list'];

$redundant = array_values($a);
$alreadyLower = strtolower($redundant[0]);
```
