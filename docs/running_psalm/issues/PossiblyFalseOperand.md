# PossiblyFalseOperand

Emitted when using a possibly `false` value as part of an operation (e.g. `+`, `.`, `^` etc).

```php
function foo(string $a) : void {
    echo strpos($a, ":") + 5;
}
```
