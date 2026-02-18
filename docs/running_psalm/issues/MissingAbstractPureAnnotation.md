# MissingAbstractPureAnnotation

Emitted when an abstract method does not have a mutability annotation, one of:

* `@psalm-pure` - Enforces all implementors to be pure (no mutations and no property accesses allowed)
* `@psalm-mutation-free` - Enforces all implementors to be mutation free (only read-only property accesses on `$this` allowed)
* `@psalm-external-mutation-free` - Enforces all implementors to be externally mutation free (only read and write property accesses on `$this` or `self` allowed)
* `@psalm-impure` - Allows all mutations (not recommended)

This issue is emitted to aid [security analysis](https://psalm.dev/docs/security_analysis/), which works best when all explicitly pure functions and methods are marked as pure.  

```php
<?php

interface a {
    public function someMethod(): void;
}
```
