# MissingInterfaceImmutableAnnotation

Emitted when an interface is not annotated with `@psalm-pure`, `@psalm-immutable`, `@psalm-external-mutation-free` or `@psalm-mutable`: to fix, mark the interface with one of the two annotations, enforcing immutability (or mutability) for all properties and methods of implementing classes.

This issue is emitted to aid [security analysis](https://psalm.dev/docs/security_analysis/), which works best when all explicitly immutable interfaces and classes are marked as immutable.  

```php
<?php

/** @api */
interface SomethingPotentiallyImmutable {
    public function someInteger() : int;
}

final class A implements SomethingPotentiallyImmutable {
    public function someInteger() : int {
        return 0;
    }
}
```
