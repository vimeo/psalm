# DivisionByZero

Emitted when dividing by a litteral 0 value

```php
<?php

$a = [];
$b = 5 / count($a);
```

## Why this is bad

The above code produces a fatal error in PHP.
