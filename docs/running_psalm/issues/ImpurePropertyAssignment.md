# ImpurePropertyAssignment

Emitted when updating a property value from a function or method marked as pure.

```php
<?php

class A {
    public int $a = 5;
}

/** @psalm-pure */
function filterOdd(int $i, A $a) : ?int {
    $a->a++;

    if ($i % 2 === 0 || $a->a === 2) {
        return $i;
    }

    return null;
}
```
