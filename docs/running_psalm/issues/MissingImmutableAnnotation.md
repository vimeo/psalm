# MissingImmutableAnnotation

Emitted when a class inheriting from an immutable interface or class does not also have a `@psalm-immutable` declaration.

Also emitted when a potentially immutable interface or class does not have a `@psalm-immutable` declaration.  

To automatically add immutable annotations where needed, run Psalm with `--alter --issues=MissingImmutableAnnotation`.  

This issue is emitted to aid [security analysis](https://psalm.dev/docs/security_analysis/), which works best when all explicitly immutable interfaces and classes are marked as immutable.  

```php
<?php

/** @psalm-immutable */
interface SomethingImmutable {
    public function someInteger() : int;
}

class MutableImplementation implements SomethingImmutable {
    private int $counter = 0;
    public function someInteger() : int {
        return ++$this->counter;
    }
}

final class CouldBeImmutable {
}

```
