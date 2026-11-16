# UnsupportedPropertyReferenceUsage

Psalm cannot guarantee the soundness of code that uses references to magic or readonly properties, or to items of
objects held by properties. References to other properties are checked against the declared type
(`ReferenceConstraintViolation`).

### Examples of Uncaught Errors

* Magic property:
```php
<?php
/**
 * @property int $b
 */
final class A {
    public function __get(string $name): int {
        return 0;
    }
}
$a = new A();
$b = &$a->b; // Notice: Indirect modification of overloaded property
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
