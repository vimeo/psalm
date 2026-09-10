# Type Variables

A type variable is a placeholder type — rendered internally as `` `_0 ``, `` `_1 ``, … —
that stands in for a class template parameter whose concrete type is not yet known. The
idea comes from the Hack typechecker's local inference: in Hack you can write
`new Vector<_>()` and let the checker solve for the type argument from how the object is
*used*. PHP has no constructor type arguments at all, so under this model **every**
templated construction site is effectively `new Foo<_>(...)`.

## How it works

When Psalm analyzes `new Foo(...)` and `Foo` has a template `T`, it mints a fresh type
variable and makes it the object's type parameter (`Foo<`_0>`). The variable starts life
with:

- **lower bounds** — whatever the constructor arguments already implied
  (`new Box(1)` seeds `` `_0 >: 1 ``); empty if nothing bound it,
- **upper bounds** — the template's declared constraint (`@template T of int|string`
  seeds `` `_0 <: int|string ``), and, when the constructor names the type exactly
  through a `class-string<T>`/`T::class` parameter, the named type as a pin
  (`new ReflectionClass(Foo::class)` pins `` `_0 = Foo ``).

From then on the variable flows through the function body, and every use *records a
constraint instead of passing judgment*: passing a value into a `T`-typed parameter adds
a lower bound; returning the object against a declared `Foo<string>` adds an equality
upper bound; arithmetic adds an `int|float` upper bound; concatenation and `echo` add
`array-key`; scalar assertions like `is_int($v)` add lower bounds. The comparator treats
a type variable as compatible with anything during these checks — the verdict is
deferred.

When the surrounding function-like (or the file's global scope) finishes analysis, all
accumulated bounds for each variable are reconciled with each other; any lower bound
that cannot satisfy an upper bound produces
[IncompatibleTypeParameters](../running_psalm/issues/IncompatibleTypeParameters.md),
reported at the position where the offending bound was recorded. The constraints
`T <: int|string, T >: int` can hold together; `T = string, T >: 1` cannot — that pair
is what makes this code report "Type 1 should be a subtype of string" *at the `new`*:

```php
<?php

/**
 * @template T of int|string
 */
class Box {
    /** @param T $t */
    public function __construct(public $t) {}
    /** @param T $item */
    public function set($item): void {
        $this->t = $item;
    }
}

/** @return Box<string> */
function bad(): Box {
    $box = new Box(1); // IncompatibleTypeParameters: Type 1 should be a subtype of string
    $box->set("two");
    return $box;
}
```

## How this differs from Psalm's previous approach

Psalm's previous model was **eager pinning**. At the construction site the template was
resolved once and frozen: to the most specific type the constructor arguments implied
(`new Box(1)` ⇒ `Box<1>`), or to its declared constraint when nothing bound it
(`new IntBox()` with `T of int` ⇒ `IntBox<int>`). Every later use was then checked
against that frozen type, argument by argument.

The practical differences:

1. **Inference direction.** Eager pinning means the constructor is the *only* place `T`
   can be learned; everything after is checking. With type variables, inference runs
   through the whole function body — a later `$box->add(5)` on an unbound `Box`
   genuinely informs what `T` is, and `$box->get()` resolves through those accumulated
   bounds instead of falling back to the constraint.

2. **Widening is legal.** Under eager pinning, `new Box(1)` pins `T = 1`, so
   `$box->set('two')` was `InvalidArgument` ("expects 1, but 'two' provided") even
   though `'two'` satisfies `T of int|string`. Under type variables the set call just
   widens `T` toward `1|'two'`, which the constraint accepts. `T` is only ever
   *required* to be something by its declared constraint, a class-string pin, or a
   context that names it (a declared return/param/property type) — not by whichever
   value happened to reach the constructor first.

3. **Where and how errors are reported.** Eager pinning reported a separate per-call
   mismatch at each offending argument, phrased against the frozen type. Reconciliation
   reports the actual contradiction between bounds, once, at the bound that cannot hold —
   so a `Box<string>` return conflicting with the constructor argument points at
   `$box = new Box(1);`, which is where the wrong value actually entered. The issue is
   its own type (`IncompatibleTypeParameters`, level 1), shown as INFO at normal error
   levels rather than a hard error.

4. **Comparisons become provisional.** Because a type variable "matches anything while
   recording a bound," any code that used a successful containment check to make a
   *decision* (negated `instanceof` narrowing, rewriting hinted closure params from an
   expected callable) treats variable-involving containments as non-definitive — they
   record evidence for reconciliation but prove nothing on their own.

## What stays the same

- Templates with a `mixed` constraint and templates with no public mutation channel
  (named only in the constructor) are never turned into type variables — there is
  nothing to check, and nothing later could constrain them — so plain `@template T`
  classes behave exactly as before.
- `SplObjectStorage` keeps its `never` special case for unbound templates.
- A type variable renders through its bounds, so inferred types still display as
  `Box<1>` or `Foo<A>` rather than `` `_0 ``.
- Callables validate against the construction-site inference with ordinary
  `InvalidArgument` messages, since a structural signature mismatch is clearer reported
  in full than deferred to reconciliation.
