# DocblockTypeContradiction

Emitted when conditional doesn't make sense given the docblock types supplied.

```php
/**
 * @param string $s
 *
 * @return void
 */
function foo($s) {
    if ($s === 5) { }
}
```
