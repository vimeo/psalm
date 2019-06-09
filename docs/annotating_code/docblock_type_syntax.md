# Docblock Type Syntax

## Union Types

An annotation of the form `Type1|Type2|Type3` is a _Union Type_. `Type1`, `Type2` and `Type3` are all acceptable possible types of that union type.

`Type1`, `Type2` and `Type3` are each [Atomic types](#atomic_types).

For example, after this statement
```php
$rabbit = rand(0, 10) === 4 ? 'rabbit' : ['rabbit'];
```
`$rabbit` will be either a `string` or an `array`. We can represent that idea with Union Types – so `$rabbit` is typed as `string|array`. Union types represent *all* the possible types a given variable can have.

Some builtin functions (such as `strpos`) can return `false` in some situations. We use union types (e.g. `string|false`) to represent that return type.

## Atomic types

A type without unions is an atomic type. Psalm allows many different sorts of basic atomic types:

### [Scalar types](type_syntax/scalar_types.md)

- [`int`](type_syntax/scalar_types.md)
- [`float`](type_syntax/scalar_types.md)
- [`string`](type_syntax/scalar_types.md)
- [`class-string`/`class-string<Foo>`](type_syntax/scalar_types.md#class-string)
- [`trait-string`](type_syntax/scalar_types.md#trait-string)
- [`callable-string`](type_syntax/scalar_types.md#callable-string)
- [`numeric-string`](type_syntax/scalar_types.md#numeric-string)
- [`bool`](type_syntax/scalar_types.md)
- [`array-key`](type_syntax/scalar_types.md#array-key)
- [`numeric`](type_syntax/scalar_types.md#numeric)
- [`scalar`](type_syntax/scalar_types.md#scalar)

### [Object types](type_syntax/object_types.md)

- [`object`](type_syntax/object_types.md)
- [`Exception`/`Foo\MyClass`/`Foo\MyClass<Bar>`](type_syntax/object_types.md)

### [Array types](type_syntax/array_types.md)

- [`array`/`non-empty-array`](type_syntax/array_types.md)
- [`string[]`](type_syntax/array_types.md#phpdoc-syntax)
- [`array<int, string>`](type_syntax/array_types.md#generic-arrays)
- [`array{foo: int, bar: string}`](type_syntax/array_types.md#object-like-arrays)

### [Callable types](type_syntax/callable_types.md)

- [`callable`/`Closure`/`callable(Foo, Bar):Baz`](type_syntax/callable_types.md)

### [Value types](type_syntax/value_types.md)

- [`null`](type_syntax/value_types.md#null)
- [`true`/`false`](type_syntax/value_types.md#true-false)
- [`6`/`7.0`/`"fourty-two"`/`'fourty two'`](type_syntax/value_types.md#some_string-4-314)
- [`Foo\Bar::MY_SCALAR_CONST`](type_syntax/value_types.md#regular-class-constants)

### Magical types

- `key-of<Foo\Bar::ARRAY_CONST>`
- `value-of<Foo\Bar::ARRAY_CONST>`
- `T[K]`

### Other

#### iterable

Represents the [`iterable` pseudo-type](https://php.net/manual/en/language.types.iterable.php).

Like arrays, iterables can have type parameters e.g. `iterable<string, Foo>`.

#### Void

`void` can be used in a return type when a function does not return a value.

#### Empty

`empty` is a type that represents a lack of type - not just a lack of type information (that's where [mixed](#mixed) is useful) but where there can be no type. A good example is the type of the empty array `[]`. Psalm types this as `array<empty, empty>`.

#### Mixed

`mixed` represents a lack of type information. Psalm warns about mixed when the `totallyTyped` flag is turned on.

#### Resource

`resource` represents a [PHP resource](https://www.php.net/manual/en/language.types.resource.php).

#### no-return

`no-return` is the 'return type' for a function that can never actually return, such as `die()`, `exit()`, or a function that
always throws an exception. It may also be written as `never-return` or `never-returns`, and  is also known as the *bottom type*.

## Intersection types

An annotation of the form `Type1&Type2&Type3` is an _Intersection Type_. Any value must satisfy `Type1`, `Type2` and `Type3` simultaneously. `Type1`, `Type2` and `Type3` are all [atomic types](#atomic_types).

For example, after this statement in a PHPUnit test:
```php

$hare = $this->createMock(Hare::class);
```
`$hare` will be an instance of a class that extends `Hare`, and implements `\PHPUnit\Framework\MockObject\MockObject`. So
`$hare` is typed as `Hare&\PHPUnit\Framework\MockObject\MockObject`. You can use this syntax whenever a value is
required to implement multiple interfaces. Only *object types* may be used within an intersection.



