# Atomic types

Atomic types are the basic building block of all type information used in Psalm. Multiple atomic types can be combined, either with [union types](union_types.md) or [intersection types](intersection_types.md). Psalm allows many different sorts of atomic types to be expressed in docblock syntax:

## [Scalar types](scalar_types.md)

- [int](scalar_types.md)
- [positive-int](scalar_types.md#positive-int)
- [float](scalar_types.md)
- [string](scalar_types.md)
- [class-string and class-string&lt;Foo&gt;](scalar_types.md#class-string-interface-string)
- [trait-string](scalar_types.md#trait-string)
- [callable-string](scalar_types.md#callable-string)
- [numeric-string](scalar_types.md#numeric-string)
- [literal-string](scalar_types.md#literal-string)
- [bool](scalar_types.md)
- [array-key](scalar_types.md#array-key)
- [numeric](scalar_types.md#numeric)
- [scalar](scalar_types.md#scalar)

## [Object types](object_types.md)

- [object](object_types.md)
- [Exception, Foo\MyClass and Foo\MyClass<Bar>](object_types.md)
- [Generator](object_types.md)

## [Array types](array_types.md)

- [array & non-empty-array](array_types.md)
- [array&lt;int, string&gt;](array_types.md#generic-arrays)
- [string\[\]](array_types.md#phpdoc-syntax)
- [list & non-empty-list](array_types.md#lists)
- [list&lt;string&gt;](array_types.md#lists)
- [array{foo: int, bar: string}](array_types.md#object-like-arrays)
- [callable-array](array_types.md#callable-array)

## [Callable types](callable_types.md)

- [callable, Closure and callable(Foo, Bar):Baz](callable_types.md)

## [Value types](value_types.md)

- [null](value_types.md#null)
- [true, false](value_types.md#true-false)
- [6, 7.0, "forty-two" and 'forty two'](value_types.md#some_string-4-314)
- [Foo\Bar::MY_SCALAR_CONST](value_types.md#regular-class-constants)

## Magical types

- [(T is true ? string : bool)](conditional_types.md)
- `key-of<Foo\Bar::ARRAY_CONST>`
- `value-of<Foo\Bar::ARRAY_CONST>`
- `T[K]`

## Top types, bottom types and empty

### `mixed`

This is the _top type_ in PHP's type system, and represents a lack of type information. Psalm warns about `mixed` types when the `totallyTyped` flag is turned on, or when you're on level 1.

### `never`

This is the _bottom type_ in PHP's type system, and usually represents a return type for a function that can never actually return, such as `die()`, `exit()`, or a function that always throws an exception. It may also be written in docblocks as `no-return` or `never-return`.

### `empty`

A type that's equivalent to a "coming soon" sign. Psalm uses this type when it’s awaiting more information — a good example is the type of the empty array `[]`, which Psalm types as `array<empty, empty>`. Psalm treats `empty` in a somewhat similar fashion to `never` when combining types together — `empty|int` becomes `int`, just as `never|string` becomes `string`.

## Other

- `iterable` - represents the [iterable pseudo-type](https://php.net/manual/en/language.types.iterable.php). Like arrays, iterables can have type parameters e.g. `iterable<string, Foo>`.
- `void` - can be used in a return type when a function does not return a value.
- `resource` represents a [PHP resource](https://www.php.net/manual/en/language.types.resource.php).
