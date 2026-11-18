# IntMaskComparison

Emitted when a bit set, i.e. a value typed as `int-mask<…>` or `int-mask-of<…>`, is compared by value with something other than `0`, the set of all its bits or another bit set.

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

Two bit sets may be compared with each other, to tell whether they are the same set:

```php
<?php

final class Permission
{
    public const READ = 1;
    public const WRITE = 2;
    public const DELETE = 4;
}

/**
 * @param int-mask-of<Permission::*> $before
 * @param int-mask-of<Permission::*> $after
 */
function changed(int $before, int $after): bool
{
    return $before !== $after; // no issue
}
```

The check covers `===`, `!==`, `==`, `!=`, `<`, `<=`, `>`, `>=` and `<=>` (ordering comparisons only allow `0`), `match` arms, `switch` cases, and the search value of `in_array()`, `array_search()` and `array_keys()`.
