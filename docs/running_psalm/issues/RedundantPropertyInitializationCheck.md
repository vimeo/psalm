# RedundantPropertyInitializationCheck

Unitialized properties are hard to statically analyze. To prevent mistakes, Psalm will enforce that all properties should be initialized.

It does that through [PropertyNotSetInConstructor](./PropertyNotSetInConstructor.md) and [MissingConstructor](./MissingConstructor.md).

Psalm will then assume every property in the codebase is initialized.

Doing that allows it to report missing initializations as well as this issue.

This issue is emitted when checking `isset()` on a non-nullable property. Because every property is assumed to be initialized, this check is redundant

If your project relies on having uninitialized properties, it is advised to suppress this issue, as well as [PropertyNotSetInConstructor](./PropertyNotSetInConstructor.md) and [MissingConstructor](./MissingConstructor.md).

```php
<?php
    class A {
        public string $bar;
        public function getBar() : string {
            if (isset($this->bar)) {
                return $this->bar;
            }
            return "hello";
        }
    }
```
