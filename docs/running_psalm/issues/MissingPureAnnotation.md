# MissingPureAnnotation

Emitted when a potentially pure function or method does not have a `@psalm-pure` or `@psalm-capabilities` declaration: the issue suggests the capabilities it was inferred to need.  

A function-like explicitly marked `@psalm-impure` is left alone: for example a hook that overriding methods may implement freely.  

No annotation is suggested for an unannotated method that is overridden, nor for the code calling it: the overrides may do more than its own body does, and the annotation would restrict them.  

No annotation is suggested for a function-like whose parameter default values need capabilities the annotation would not give them (see [`@psalm-capabilities`](../../annotating_code/supported_annotations.md#psalm-capabilities)).  

To automatically add pure annotations where needed, run Psalm with `--alter --issues=MissingPureAnnotation`.  

This issue is emitted to aid [security analysis](https://psalm.dev/docs/security_analysis/), which works best when all explicitly pure functions and methods are marked as pure.  

```php
<?php

function couldBePure(int $a): int {
    return $a+1;
}
```

A function or method (not a closure) is inferred without what the `_` purity of its parameters (`Closure[_]`, `Traversable[_]`, see [`@psalm-purity-template`](../../annotating_code/supported_annotations.md#psalm-purity-template)) would charge to its callers: calling the closures and callables found in a parameter with the default purity, directly (`$f()`), in its elements (`$fs[0]()`, `foreach ($fs as $f) { $f(); }`) or in what they return (`$f()()`); iterating over an iterable parameter; and calling a method whose purity depends on a purity argument of a parameter (`Doer[_]` for a `Doer` with a `@psalm-purity-template`). The issue then names the parameters, and `--alter` adds the `_` where it stands, along with the purity annotation.

```php
<?php

/**
 * @param list<Closure(int): int> $fs
 */
function applyAll(array $fs, int $x): int {
    foreach ($fs as $f) {
        $x = $f($x); // with --alter, $fs becomes list<Closure[_](int): int> and applyAll @psalm-pure
    }
    return $x;
}
```

Only what the function-like's own body does counts, not what the closures it creates do: those may be returned or stored rather than called. The parameter must not be assigned in the body, and the `_` must be writable where the parameter's type is written (not behind a `@psalm-type` alias, and not where another purity is written).

Purity is inferred as a fixpoint over the call graph after the whole codebase has been analysed: a function-like that only calls other pure (or as-yet-unannotated but inferred-pure) function-likes is itself reported, regardless of the order in which they are declared, and mutual recursion, recursive closures and closures assigned to a variable are handled.
