# MixedMethodCall

Emitted when calling a method on a value that Psalm cannot infer a type for

```php
/** @param mixed $a */
function foo($a) : void {
    $a->foo();
}
```
