# MissingImmutableAnnotation

Emitted when a potentially immutable interface or class does not have a `@psalm-pure`, `@psalm-immutable` or `@psalm-external-mutation-free` declaration.  

To automatically add immutable annotations where needed, run Psalm with `--alter --issues=MissingImmutableAnnotation`.  

This issue is emitted to aid [security analysis](https://psalm.dev/docs/security_analysis/), which works best when all explicitly immutable interfaces and classes are marked as immutable.  

```php
<?php

/** @api */
final class CouldBeExternallyMutationFree {
    private int $counter = 0;

    /** @psalm-external-mutation-free */
    public function someInteger() : int {
        return ++$this->counter;
    }
}

/** @api */
final class CouldBeImmutable {
}

```
