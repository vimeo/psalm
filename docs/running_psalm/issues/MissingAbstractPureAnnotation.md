# MissingAbstractPureAnnotation

Emitted when the config flag `suggestPurityAnnotations` is set to `true` and an abstract method does not have a `@psalm-pure`, `@psalm-impure` or [`@psalm-capabilities`](../../annotating_code/supported_annotations.md#psalm-capabilities)
annotation, which says what every implementation may do, for example:

* `@psalm-pure` - no side effects and no property accesses
* `@psalm-capabilities read-props` - may read properties, but not write them
* `@psalm-capabilities read-props|write-this-props` - may also write the properties of `$this`
* `@psalm-impure` - may do anything (not recommended)

```php
<?php

interface a {
    public function someMethod(): void;
}
```

## Why this is useful

An abstract method has no body to infer anything from, so Psalm has to assume the implementations may do anything. The annotation says what they may do, and every implementation is then checked against it (`ImmutableDependency`). For [taint analysis](https://psalm.dev/docs/security_analysis/), an annotated `@psalm-pure` method keeps the taint of its arguments apart from one call to the next, which cannot be inferred for abstract and overridable methods: see [specializing taints in functions](../../security_analysis/avoiding_false_positives.md#specializing-taints-in-functions).

## How to enable

The suggestion is off by default. Opt in in `psalm.xml`:

```xml
<psalm suggestPurityAnnotations="true">
```

See [class-level contracts](../../annotating_code/purity_model.md#class-level-contracts) in the purity model.
