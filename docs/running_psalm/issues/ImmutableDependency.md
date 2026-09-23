# ImmutableDependency

Emitted when an mutable class inherits from an immutable class, trait or interface.

```php
<?php

/** @psalm-immutable */
class ImmutableParent {
    public int $i = 0;

    public function getI(): int {
        return $this->i;
    }
}

final class MutableChild extends ImmutableParent {
    public function setI(int $i): void {
        $this->i = 123;
    }
}

// This is bad because when passing around an ImmutableParent instance,
// we might actually be passing around a MutableChild.  
```

Will also be emitted for classes marked `@psalm-pure`, `@psalm-mutation-free`, `@psalm-external-mutation-free`
or `@psalm-capabilities`, and for a method override that needs more capabilities than the method it overrides:

```php
<?php

abstract class Parent_ {
    /** @psalm-pure */
    abstract public function get(): int;
}

final class Child extends Parent_ {
    /** @psalm-external-mutation-free */
    public function get(): int { return 1; } // needs more than the pure method it overrides
}
```

To fix, make the child need the same (or fewer) capabilities than the parent, or vice versa.  