# MissingPureAnnotation

Emitted when a potentially pure function or method does not have a `@psalm-pure` declaration.  

To automatically add pure annotations where needed, run Psalm with `--alter --issues=MissingPureAnnotation`.  

This issue is emitted to aid [security analysis](https://psalm.dev/docs/security_analysis/), which works best when all explicitly pure functions and methods are marked as pure.  

```php
<?php

function couldBePure(int $a): int {
    return $a+1;
}
```

Purity is inferred as a fixpoint over the call graph after the whole codebase has been analysed: a function-like that only calls other pure (or as-yet-unannotated but inferred-pure) function-likes is itself reported, regardless of the order in which they are declared, and mutual recursion, recursive closures and closures assigned to a variable are handled.
