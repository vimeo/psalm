# NoValue

Emitted when using the result of a function that never returns.

```php
/**
 * @return never-returns
 */
function foo() : void {
    exit();
}

$a = foo();
```
