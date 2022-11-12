# RedundantFunctionCallGivenDocblockType

Emitted when function call is redundant given information supplied in one or more docblocks.

This may be desired (e.g. when checking user input) so is distinct from RedundantFunctionCall, which only applies to non-docblock types.

```php
<?php

/**
 * @param array{0: lowercase-string, 1: non-empty-list<lowercase-string>} $s
 *
 * @return lowercase-string
 */
function foo($s): string {
    $redundantList = array_values($s);
    $redundantSubList = array_values($s[1]);
    $redundantLowercase = strtolower($redundantSubList[0]);
    return $redundantLowercase;
}
```
