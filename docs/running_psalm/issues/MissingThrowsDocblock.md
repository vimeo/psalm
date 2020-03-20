# MissingThrowsDocblock

Emitted when a function doesn't have a return type defined

```php
<?php

function foo(int $x, int $y) : int {
    if ($y === 0) {
        throw new \InvalidArgumentException('Cannot divide by zero');
    }

    return intdiv($x, $y);
}
```
