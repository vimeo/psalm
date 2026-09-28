# MissingPureAnnotation

Emitted when a potentially pure function or method does not have a `@psalm-pure` or `@psalm-capabilities` declaration: the issue suggests the capabilities it was inferred to need.  

To automatically add pure annotations where needed, run Psalm with `--alter --issues=MissingPureAnnotation`.  

This issue is emitted to aid [security analysis](https://psalm.dev/docs/security_analysis/), which works best when all explicitly pure functions and methods are marked as pure.  

```php
<?php

function couldBePure(int $a): int {
    return $a+1;
}
```

A function-like that calls one of its closure or callable parameters is inferred without those calls when giving the parameter the `_` purity (`Closure[_]`, `callable[_]`, see [`@psalm-purity-template`](../../annotating_code/supported_annotations.md#psalm-purity-template)) would charge them to its callers: the issue then names the parameters, and `--alter` adds the `_` to their types along with the purity annotation.

```php
<?php

/**
 * @param Closure(int): int $f
 */
function apply(Closure $f): int {
    return $f(1); // with --alter, $f becomes Closure[_](int): int and apply @psalm-pure
}
```

Purity is inferred as a fixpoint over the call graph after the whole codebase has been analysed: a function-like that only calls other pure (or as-yet-unannotated but inferred-pure) function-likes is itself reported, regardless of the order in which they are declared, and mutual recursion, recursive closures and closures assigned to a variable are handled.
