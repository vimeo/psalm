# ReferenceConstraintViolation

Emitted when changing the type of a pass-by-reference variable

```php
function foo(string &$a) {
    $a = 5;
}
```
