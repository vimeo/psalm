# MissingPureAnnotation

Emitted when the config flag `suggestPurityAnnotations` is set to `true` and a potentially pure function or method does not have a `@psalm-pure` or `@psalm-capabilities` declaration: the issue suggests the capabilities it was inferred to need.  

A function-like explicitly marked `@psalm-impure` is left alone: for example a hook that overriding methods may implement freely.  

No annotation is suggested for an unannotated method that is overridden, nor for the code calling it: the overrides may do more than its own body does, and the annotation would restrict them.  

No annotation is suggested for a function-like whose parameter default values need capabilities the annotation would not give them (see [parameter default values](../../annotating_code/purity_model.md#parameter-default-values)).  

To automatically add pure annotations where needed, run Psalm with `--alter --issues=MissingPureAnnotation` (it does not need the config flag).  

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

## Why this is useful

The annotation documents what the function may do, and Psalm then enforces it: a later change that adds a side effect is reported as `ImpureFunctionCall` instead of silently making every caller impure.

It also helps [taint analysis](https://psalm.dev/docs/security_analysis/), which keeps the taint of the arguments of each call to a pure function apart. Psalm infers that for functions and for methods that cannot be overridden (`private` or `final` methods, and methods of `final` classes). For an overridable method or a constructor the annotation is the only way to say it: see [specializing taints in functions](../../security_analysis/avoiding_false_positives.md#specializing-taints-in-functions).

## How to enable

The suggestion is off by default. Opt in in `psalm.xml`:

```xml
<psalm suggestPurityAnnotations="true">
```

See [inferring annotations](../../annotating_code/purity_model.md#inferring-annotations) in the purity model for how purity is inferred.
