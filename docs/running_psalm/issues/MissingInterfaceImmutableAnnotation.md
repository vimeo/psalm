# MissingInterfaceImmutableAnnotation

Emitted when the config flag `suggestPurityAnnotations` is set to `true` and an interface is not annotated with `@psalm-pure`, `@psalm-immutable`, `@psalm-capabilities` or `@psalm-mutable`, and declares a method without a purity annotation of its own (or a `@psalm-purity-from-template`): to fix, mark the interface with one of them, which then applies to all properties and methods of implementing classes. An interface whose methods all say what they may do needs no class-level annotation. Nor does a marker interface, which declares no method or property and inherits none: it does nothing, so it is pure, without capping its implementers.

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

## Why this is useful

The annotation caps what every implementation may do, and an implementation that does more is reported as `ImmutableDependency`. An interface method has no body to infer anything from, so this is the only way to say what it may do. Explicit purity is also what [taint analysis](https://psalm.dev/docs/security_analysis/) relies on where it cannot infer it, see [specializing taints in functions](../../security_analysis/avoiding_false_positives.md#specializing-taints-in-functions).

## How to enable

The suggestion is off by default. Opt in in `psalm.xml`:

```xml
<psalm suggestPurityAnnotations="true">
```

See [class-level contracts](../../annotating_code/purity_model.md#class-level-contracts) in the purity model.
