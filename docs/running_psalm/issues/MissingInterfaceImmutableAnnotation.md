# MissingInterfaceImmutableAnnotation

Emitted when an interface is not annotated with `@psalm-pure`, `@psalm-immutable`, `@psalm-capabilities` or `@psalm-mutable`, and declares a method without a purity annotation of its own (or a `@psalm-purity-from-template`): to fix, mark the interface with one of them, which then applies to all properties and methods of implementing classes. An interface declaring no method, or whose methods all say what they may do, needs no class-level annotation.

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

See [class-level contracts](../../annotating_code/purity_model.md#class-level-contracts) in the purity model.
