# DirectConstructorCall

Emitted when `__construct()` is called directly as a method. Constructors are supposed to be called implicitly, as a result of `new ClassName` statement.

```php
<?php
class A {
    public function __construct() {}
}
$a = new A;
$a->__construct(); // wrong
```

The initializer of a lazy ghost (PHP 8.4) may construct the object it is given:

```php
<?php
final class Example {
    public function __construct(public int $prop) {}
}
$reflector = new ReflectionClass(Example::class);
$ghost = $reflector->newLazyGhost(function (Example $object): void {
    $object->__construct(1); // no issue
});
```
