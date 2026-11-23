# Intersection types

An annotation of the form `Type1&Type2&Type3` is an _Intersection Type_. Any value must satisfy `Type1`, `Type2` and `Type3` simultaneously. `Type1`, `Type2` and `Type3` are all [atomic types](atomic_types.md).

For example, after this statement in a PHPUnit test:

```php
<?php

$hare = $this->createMock(Hare::class);
```

`$hare` will be an instance of a class that extends `Hare`, and implements `\PHPUnit\Framework\MockObject\MockObject`. So
`$hare` is typed as `Hare&\PHPUnit\Framework\MockObject\MockObject`. You can use this syntax whenever a value is
required to implement multiple interfaces.

Another use case is being able to merge object-like arrays:

```php
/**
 * @psalm-type A=array{a: int}
 * @psalm-type B=array{b: int}
 *
 * @param A $a
 * @param B $b
 *
 * @return A&B
 */
function foo($a, $b) {
    return $a + $b;
}
```

The returned type will contain the properties of both `A` and `B`. In other words, it will be `{a: int, b: int}`.

Intersections are only valid for lists of only *object types* and lists of only *object-like arrays*.

You can also intersect a class with an object shape to describe properties the class doesn't declare. This helps with
classes that read properties through the magic `__get` method, where Psalm can't know which properties exist:

```php
<?php

class Row
{
    /** @param array<string, mixed> $columns */
    public function __construct(private array $columns) {}

    public function __get(string $name): mixed
    {
        return $this->columns[$name] ?? null;
    }
}

/** @param Row&object{email: string} $user */
function sendWelcomeEmail(Row $user): void
{
    mail($user->email, 'Welcome!', 'Thanks for signing up.');
}
```

With a plain `Row`, `$user->email` would be `mixed`. With `Row&object{email: string}` it is a `string`.

Psalm takes a property from the shape only when the class doesn't declare it. The shape takes priority over a property
that comes from a `@mixin`. A `final` class can only have undeclared properties through `__get`, so for a final class
without `__get` (its own or inherited) Psalm still reports `UndefinedPropertyFetch`.
