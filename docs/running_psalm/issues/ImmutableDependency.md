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

Will also be emitted for classes marked `@psalm-pure`, `@psalm-mutation-free`, `@psalm-external-mutation-free`.  

To fix, make the child have the same mutability level of the parent, or vice versa.  