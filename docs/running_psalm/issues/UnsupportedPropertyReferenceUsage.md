# UnsupportedPropertyReferenceUsage

Psalm cannot guarantee the soundness of code that uses references to properties.

### Examples of Uncaught Errors

* Instance property assigned wrong type:
```php
<?php
class A {
    public int $b = 0;
}
$a = new A();
$b = &$a->b;
$b = ''; // Fatal error
```

* Static property assigned wrong type:
```php
<?php
class A {
    public static int $b = 0;
}
$b = &A::$b;
$b = ''; // Fatal error
```

* Readonly property reassigned:
```php
<?php
class A {
    public function __construct(
        public readonly int $b,
    ) {
    }
}
$a = new A(0);
$b = &$a->b;
$b = 1; // Fatal error
```
