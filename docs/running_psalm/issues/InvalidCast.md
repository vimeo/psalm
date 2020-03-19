# InvalidCast

Emitted when attempting to cast a value that's not castable

```php
class A {}
$a = new A();
$b = (string)$a;
```
