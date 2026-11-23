# UncaughtThrowInGlobalScope

Emitted when a possible exception isn't caught in global scope

```php
<?php // checkForThrowsDocblock="true" checkForThrowsInGlobalScope="true"

/**
 * @throws \Exception
 */
function foo() : int {
    return random_int(0, 1);
}
foo();
```
