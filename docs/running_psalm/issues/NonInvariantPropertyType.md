# NonInvariantPropertyType

Emitted when a public or protected class property has a different type to the matching property in the parent class.

```php
<?php

class A {
    public string $foo = 'hello';
}

class B extends A {
    public ?string $foo;
}

```

## Why this is bad

For typed properties, this can cause a compile error.
