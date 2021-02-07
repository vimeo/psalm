# NonInvariantDocblockPropertyType

Emitted when a public or protected class property has a different docblock type to the matching property in the parent class.

```php
<?php

class A {
    /** @var string */
    public $foo = 'hello';
}

class B extends A {
    /** @var null|string */
    public $foo;
}

```

## Why this is bad

For non-typed properties, it can cause a type system violation when code written against the parent class reads or writes a value on an object of the child class.

If the child class widens the type then reading the value may return unexpected value that client code cannot deal with. If the child class narrows the type then code setting the value may set
it to an invalid value.
