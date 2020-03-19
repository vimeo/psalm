# RedundantCondition

Emitted when conditional is redundant given previous assertions

```php
class A {}
function foo(A $a) : ?A {
    if ($a) return $a;
    return null;
}
```
