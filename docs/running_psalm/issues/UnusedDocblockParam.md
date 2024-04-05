# UnusedDocblockParam

Emitted when `--find-dead-code` is turned on and a parameter specified in docblock does not have a corresponding parameter in function / method signature.

```php
<?php

/**
 * @param string $legacy_param was renamed to $newParam
 */
function f(string $newParam): string {
    return strtolower($newParam);
}
```
