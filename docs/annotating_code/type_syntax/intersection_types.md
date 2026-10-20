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

Intersections of objects are only valid for lists of only *object types* and lists of only *object-like arrays*.

Scalar types can be intersected too: `lowercase-string&non-empty-string` is a `non-empty-lowercase-string`,
`lowercase-string&non-falsy-string` a non-falsy lowercase string, `int<0, max>&int<min, 10>` an `int<0, 10>`,
`int&int-mask<1, 2>` is `0|1|2|3`, `array-key&string` is `string` and `string&callable` is a `callable-string`.
An intersection Psalm has no exact type for (e.g. `numeric-string&non-falsy-string`) is reported as an invalid
docblock, and so is one no value can have (e.g. `int<0, 5>&int<6, 10>`).

A conditional type can be intersected with other types: `(T is int ? A : B)&C` is `(T is int ? A&C : B&C)`, a
branch no value can have becoming `never`.

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
