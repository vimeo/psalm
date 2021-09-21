# RedundantCastGivenDocblockType

Emitted when a cast (string, int, float etc.) is redundant given the docblock-provided type

```php
<?php
/**
 * @param  string $s
 */
function foo($s) : string {
    return (string) $s;
}
```
