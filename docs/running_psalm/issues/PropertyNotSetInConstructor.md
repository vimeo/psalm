# PropertyNotSetInConstructor

Uninitialized properties are hard to statically analyze. To prevent mistakes, Psalm will enforce that all properties should be initialized.

It does that through [MissingConstructor](./MissingConstructor.md) and this issue.

Psalm will then assume every property in the codebase is initialized.

Doing that allows it to report missing initializations as well as [RedundantPropertyInitializationCheck](./RedundantPropertyInitializationCheck.md)

This issue is emitted when a non-null property without a default value is declared but not set in the class’s constructor

If your project relies on having uninitialized properties, it is advised to suppress this issue, as well as [MissingConstructor](./MissingConstructor.md) and [RedundantPropertyInitializationCheck](./RedundantPropertyInitializationCheck.md).

```php
<?php

class A {
    /** @var string */
    public $foo;

    public function __construct() {}
}
```
