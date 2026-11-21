# DeprecatedMethod

Emitted when calling a deprecated method on a given class:

```php
<?php

class A {
    /** @deprecated */
    public function foo() : void {}
}
(new A())->foo();
```

Psalm also reports the builtin methods PHP deprecates, whatever PHP version you analyse:

```php
<?php

function sortItems(ArrayIterator $items, SplFileObject $file): void {
    $items->asort(); // DeprecatedMethod
    $row = $file->fgetcsv(); // DeprecatedMethod: use fgetcsv() on a stream instead
}
```

## Why this is bad

The `@deprecated` tag is normally indicative of code that will stop working in the near future.

## How to fix

Don’t use the deprecated method.
