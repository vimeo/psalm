# MissingImmutableAnnotation

Emitted when the config flag `suggestPurityAnnotations` is set to `true` and a potentially immutable interface or class does not have a `@psalm-pure`, `@psalm-immutable` or `@psalm-capabilities` declaration.  

To automatically add immutable annotations where needed, run Psalm with `--alter --issues=MissingImmutableAnnotation` (it does not need the config flag).  

```php
<?php

/** @api */
final class CouldBeExternallyMutationFree {
    private int $counter = 0;

    /** @psalm-capabilities read-props|write-this-props */
    public function someInteger() : int {
        return ++$this->counter;
    }
}

/** @api */
final class CouldBeImmutable {
}

```

## Why this is useful

The annotation is a contract for the class and its subclasses: a method of the class, or of a subclass, that does more than the contract allows is reported, so the class cannot silently become mutable. Explicit purity is also what [taint analysis](https://psalm.dev/docs/security_analysis/) relies on where it cannot infer it, see [specializing taints in functions](../../security_analysis/avoiding_false_positives.md#specializing-taints-in-functions).

## How to enable

The suggestion is off by default. Opt in in `psalm.xml`:

```xml
<psalm suggestPurityAnnotations="true">
```

See [inferring annotations](../../annotating_code/purity_model.md#inferring-annotations) in the purity model.
