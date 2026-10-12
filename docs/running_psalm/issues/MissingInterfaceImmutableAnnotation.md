# MissingInterfaceImmutableAnnotation

Emitted when an interface is not annotated with `@psalm-pure`, `@psalm-immutable`, `@psalm-capabilities` or `@psalm-mutable`: to fix, mark the interface with one of them, which then applies to all properties and methods of implementing classes. An interface whose methods all say what they may do, with a purity annotation of their own or a `@psalm-purity-from-template`, needs no class-level annotation. A marker interface, declaring no method, still does.

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
