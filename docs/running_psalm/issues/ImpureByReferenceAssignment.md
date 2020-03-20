# ImpureByReferenceAssignment

Emitted when assigning a passed-by-reference variable inside a function or method marked as mutation-free.

```php
<?php

/**
 * @psalm-pure
 */
function foo(string &$a): string {
    $a = "B";
    return $a;
}
```
