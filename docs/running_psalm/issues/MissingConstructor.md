# MissingConstructor

Unitialized properties are statically hard to analyze. To prevent mistakes, Psalm will enforce that all properties should be initialized.

It does that through [PropertyNotSetInConstructor](./PropertyNotSetInConstructor.md) and this issue.

Psalm will then assume every property in the codebase is initialized.

Doing that allows it to report missing initializations as well as [RedundantPropertyInitializationCheck](./RedundantPropertyInitializationCheck.md)

This issue is emitted when non-null properties without default values are defined in a class without a `__construct` method

If your project relies on having uninitialized properties, it is advised to suppress this issue, as well as [PropertyNotSetInConstructor](./PropertyNotSetInConstructor.md) and [RedundantPropertyInitializationCheck](./RedundantPropertyInitializationCheck.md).

```php
<?php

class A {
    /** @var string */
    public $foo;
}
```
