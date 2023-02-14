# ParadoxicalCondition

Emitted when a paradox is encountered in your programs logic that could not be caught by `RedundantCondition`

```php
<?php

function foo(string $input) : string {
    return $input === "a" ? "bar" : ($input === "a" ? "foo" : "b");
}
```
