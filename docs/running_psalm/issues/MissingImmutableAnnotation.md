# MissingImmutableAnnotation

Emitted when a potentially immutable interface or class does not have a `@psalm-pure`, `@psalm-immutable` or `@psalm-capabilities` declaration.  

To automatically add immutable annotations where needed, run Psalm with `--alter --issues=MissingImmutableAnnotation`.  

This issue is emitted to aid [security analysis](https://psalm.dev/docs/security_analysis/), which works best when all explicitly immutable interfaces and classes are marked as immutable.  

Taint analysis can [specialize](../../security_analysis/avoiding_false_positives.md#specializing-taints-in-classes) the taints of an immutable class per instance, instead of mixing the taints of every instance. The annotation is also enforced, so a later change that mutates the class is reported (see the [purity model](../../annotating_code/purity_model.md#class-level-contracts)).  

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

See [inferring annotations](../../annotating_code/purity_model.md#inferring-annotations) in the purity model.
