# Scalar types

`int`, `bool`, `float`, `string` are examples of scalar types. Scalar types represent scalar values in PHP. These types are also valid types in PHP 7.

### scalar

The type `scalar` is the supertype of all scalar types.

### array-key

`array-key` is the supertype (but not a union) of `int` and `string`.

### positive-int

`positive-int` allows only positive integers (equivalent to `int<1, max>`)

### numeric

`numeric` is a supertype of `int` or `float` and [`numeric-string`](#numeric-string).

### class-string, interface-string

Psalm supports a special meta-type for `MyClass::class` constants, `class-string`, which can be used everywhere `string` can.

For example, given a function with a `string` parameter `$class_name`, you can use the annotation `@param class-string $class_name` to tell Psalm make sure that the function is always called with a `::class` constant in that position:

```php
<?php
class A {}

/**
 * @param class-string $s
 */
function takesClassName(string $s) : void {}
```

`takesClassName("A");` would trigger a `TypeCoercion` issue, whereas `takesClassName(A::class)` is fine.

You can also parameterize `class-string` with an object name e.g. [`class-string<Foo>`](value_types.md#regular-class-constants). This tells Psalm that any matching type must either be a class string of `Foo` or one of its descendants.

### trait-string

Psalm also supports a `trait-string` annotation denoting a trait that exists.

### enum-string

Psalm also supports a `enum-string` annotation denote an enum that exists.

### callable-string

`callable-string` denotes a string value that has passed an `is_callable` check.

### numeric-string

`numeric-string` denotes a string value that has passed an `is_numeric` check.

### literal-string

`literal-string` denotes a string value that is entirely composed of strings in your application.

Examples:

- `"hello " . "world"`
- `"hello " . Person::DEFAULT_NAME`
- `implode(', ', ["one", "two"])`
- `implode(', ', [1, 2, 3])`
- `"hello " . <another literal-string>`

Strings that don't pass this type check:

- `file_get_contents("foo.txt")`
- `$_GET["foo"]`
- `"hello " . $_GET["foo"]`

### lowercase-string, non-empty-string, non-empty-lowercase-string

A non empty string, lowercased or both at once.

`empty` here is defined as all strings except the empty string `''`. Another type `non-falsy-string` is effectively a subtype of `non-empty-string`, and also precludes the string value `'0'`.
