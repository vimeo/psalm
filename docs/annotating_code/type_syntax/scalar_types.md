# Scalar types

## scalar

`int`, `bool`, `float`, `string` are examples of scalar types. Scalar types represent scalar values in PHP. These types are also valid types in PHP 7.
The type `scalar` is the supertype of all scalar types.

## int-range

Integer ranges indicate an integer within a range, specified using generic syntax: `int<x, y>`.  
`x` and `y` must be integer numbers.  
`x` can also be `min` to indicate PHP_INT_MIN, and `y` can be `max` to indicate PHP_INT_MAX.

Examples:

* `int<-1, 3>`
* `int<min, 0>`
* `int<1, max>` (equivalent to `positive-int`)
* `int<0, max>` (equivalent to `non-negative-int`)
* `int<min, -1>` (equivalent to `negative-int`)
* `int<min, 0>` (equivalent to `non-positive-int`)
* `int<min, max>` (equivalent to `int`)

## int-mask&lt;1, 2, 4&gt;

Represents the type that is the result of a bitmask combination of its parameters.  
`int-mask<1, 2, 4>` corresponds to `0|1|2|3|4|5|6|7`.  

## int-mask-of&lt;MyClass::CLASS_CONSTANT_*&gt;

Represents the type that is the result of a bitmask combination of its parameters.  
This is the same concept as [`int-mask`](#int-mask1-2-4) but this type is used with a reference to constants in code: `int-mask-of<MyClass::CLASS_CONSTANT_*>` will correspond to `0|1|2|3|4|5|6|7` if there are three constants called `CLASS_CONSTANT_{A,B,C}` with values 1, 2 and 4.  

## array-key

`array-key` is the supertype (but not a union) of `int` and `string`.

## numeric

`numeric` is a supertype of `int` or `float` and [`numeric-string`](#numeric-string).

## class-string, interface-string

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

## trait-string

Psalm also supports a `trait-string` annotation denoting a trait that exists.

## enum-string

Psalm also supports a `enum-string` annotation denote an enum that exists.

## callable-string

`callable-string` denotes a string value that has passed an `is_callable` check.

## numeric-string

`numeric-string` denotes a string value that has passed an `is_numeric` check.

## literal-string

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

## literal-int

`literal-int` denotes an int value that is entirely composed of literal integers in your application.

Examples:

- `12`
- `12+42`

Integers that don't pass this type check:

- `(int) file_get_contents("foo.txt")`
- `(int) $_GET["foo"]`
- `((int)$_GET["foo"]) + 2`

## lowercase-string, non-empty-string, non-empty-lowercase-string

A non empty string, lowercased or both at once.

`empty` here is defined as all strings except the empty string `''`. Another type `non-falsy-string` is effectively a subtype of `non-empty-string`, and also precludes the string value `'0'`.
