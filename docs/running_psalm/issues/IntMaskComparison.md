# IntMaskComparison

Emitted when a bit set, i.e. a value typed as `int-mask<…>` or `int-mask-of<…>`, is compared by value with something other than `0` or the set of all its bits.

```php
<?php

final class Permission
{
    public const READ = 1;
    public const WRITE = 2;
    public const DELETE = 4;
}

/** @param int-mask-of<Permission::*> $permissions */
function canRead(int $permissions): bool
{
    return $permissions === Permission::READ;
}
```

## Why this is bad

`$permissions === Permission::READ` is only true when `READ` is the *only* bit set: `canRead(Permission::READ | Permission::WRITE)` returns `false`. Comparing a bit set by value is almost always a mistake for a test of some of its bits.

## How to fix

Test the bits you need with `&`:

```php
<?php

final class Permission
{
    public const READ = 1;
    public const WRITE = 2;
    public const DELETE = 4;
}

/** @param int-mask-of<Permission::*> $permissions */
function canRead(int $permissions): bool
{
    return ($permissions & Permission::READ) === Permission::READ;
}
```

- `($mask & B) === B`: every bit of `B` is set;
- `($mask & B) !== 0`: at least one bit of `B` is set;
- `($mask & ~B) === 0`: no bit outside of `B` is set, i.e. the mask is a subset of `B`;
- `$mask === 0`: no bit is set;
- `$mask === ALL`, where `ALL` has every bit of the mask's type: every bit is set.

`$mask & B` is itself a bit set, of the bits of `B`, so it may only be compared with `0` or `B`.

`$a & $b` may also be compared with `$a` or `$b`, as in `($available & $required) === $required`.

The check covers `===`, `!==`, `==`, `!=`, `<`, `<=`, `>`, `>=` and `<=>` (ordering comparisons only allow `0`), `match` arms, `switch` cases, and the search value of `in_array()`, `array_search()` and `array_keys()`.

When a comparison of the exact set of bits is intended, for instance to tell whether a fixed point was reached, pass the masks to a function taking a plain `int` that documents that intent.
