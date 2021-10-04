# ParadoxicalCondition

Emitted when a paradox is encountered in your programs logic that could not be caught by `RedundantCondition`

```php
<?php

function foo($a, $b) : void {
    if ($a && $b) {
        echo "a";
    } elseif ($a && $b) {
        echo "cannot happen";
    }
}
```
