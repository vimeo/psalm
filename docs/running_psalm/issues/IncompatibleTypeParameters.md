# IncompatibleTypeParameters

Emitted when the constraints recorded for a class template parameter that could not
be inferred at the construction site turn out to be incompatible with each other.

When a templated class is constructed, Psalm tracks each of its templates as a
[type variable](../../annotating_code/type_variables.md): the constructor arguments
and each later use add constraints, which are reconciled with each other once the
surrounding function has been analyzed.

```php
<?php

/** @template T of int */
class IntBox {
    public function __construct() {}

    /** @param T $item */
    public function add($item): void {}
}

function probe(): void {
    $box = new IntBox();
    $box->add("nope");
}
```

Here nothing binds `T` when `IntBox` is constructed, so `T` must be inferred from the
later call to `add()` — but `'nope'` cannot satisfy the template's `of int` constraint.

## Why it’s bad

There is no type the template parameter could take that satisfies all of its
recorded constraints, so the object is being used in contradiction with its
own declaration.

## How to fix

Pass values that satisfy the template's declared constraint:

```php
<?php

/** @template T of int */
class IntBox {
    public function __construct() {}

    /** @param T $item */
    public function add($item): void {}
}

function probe(): void {
    $box = new IntBox();
    $box->add(42);
}
```
