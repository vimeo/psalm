# MissingPureAnnotation

Emitted when a potentially pure function or method does not have a `@psalm-pure` or `@psalm-capabilities` declaration: the issue suggests the capabilities it was inferred to need.  

A function-like explicitly marked `@psalm-impure` is left alone: for example a hook that overriding methods may implement freely.  

No annotation is suggested for an unannotated method that is overridden, nor for the code calling it: the overrides may do more than its own body does, and the annotation would restrict them.  

No annotation is suggested for a function-like whose parameter default values need capabilities the annotation would not give them (see [parameter default values](../../annotating_code/purity_model.md#parameter-default-values)).  

To automatically add pure annotations where needed, run Psalm with `--alter --issues=MissingPureAnnotation`.  

This issue is emitted to aid [security analysis](https://psalm.dev/docs/security_analysis/), which works best when all explicitly pure functions and methods are marked as pure.  

```php
<?php

function couldBePure(int $a): int {
    return $a+1;
}
```

See [inferring annotations](../../annotating_code/purity_model.md#inferring-annotations) in the purity model for how purity is inferred.
