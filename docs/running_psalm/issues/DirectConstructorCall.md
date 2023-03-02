# DirectConstructorCall

Emitted when `__construct()` is called directly as a method. Constructors are supposed to be called implicitely, as a result of `new ClassName` statement.

```php
<?php
class A {
    public function __construct() {}
}
$a = new A;
$a->__construct(); // wrong
```
