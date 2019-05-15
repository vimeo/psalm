# Docblock Type Syntax

## Union Types

An annotation of the form `Type1|Type2|Type3` is a _Union Type_. `Type1`, `Type2` and `Type3` are all acceptable possible types of that union type.

For example, after this statement
```php
$rabbit = rand(0, 10) === 4 ? 'rabbit' : ['rabbit'];
```
`$rabbit` will be either a `string` or an `array`. We can represent that idea with Union Types – so `$rabbit` is typed as `string|array`. Union types represent *all* the possible types a given variable can have.

Some builtin functions (such as `strpos`) can return `false` in some situations. We use union types (e.g. `string|false`) to represent that return type.

## Atomic types

A type without unions is an atomic type. Psalm allows many different sorts of atomic types.

### Scalar types

`int`, `bool`, `float`, `string` are examples of scalar types. Scalar types represent scalar values in PHP. These types are also valid types in PHP 7.

#### class-string

Psalm supports a special meta-type for `MyClass::class` constants, `class-string`, which can be used everywhere `string` can.

For example, given a function with a `string` parameter `$class_name`, you can use the annotation `@param class-string $class_name` to tell Psalm make sure that the function is always called with a `::class` constant in that position:

```php
class A {}

/**
 * @param class-string $s
 */
function takesClassName(string $s) : void {}
```

`takesClassName("A");` would trigger a `TypeCoercion` issue (or a `PossiblyInvalidArgument` issue if [`allowCoercionFromStringToClassConst`](configuration.md#coding-style) was set to `false` in your config), whereas `takesClassName(A::class)` is fine.

### Object types

`stdClass`, `Foo`, `Bar\Baz` etc. are examples of object types. These types are also valid types in PHP

#### Generic object types

Psalm supports using generic object types like `ArrayObject<int, string>`. Any generic object should be typehinted with appropriate [`@template` tags](templated_annotations.md).

### Intersection types

An annotation of the form `Type1&Type2&Type3` is an _Intersection Type_. Any value must satisfy `Type1`, `Type2` and `Type3` simultaneously.

For example, after this statement in a PHPUnit test:
```php

$hare = $this->createMock(Hare::class);
```
`$hare` will be an instance of a class that extends `Hare`, and implements `\PHPUnit\Framework\MockObject\MockObject`. So
`$hare` is typed as `Hare&\PHPUnit\Framework\MockObject\MockObject`. You can use this syntax whenever a value is
required to implement multiple interfaces. Only *object types* may be used within an intersection.

### Array types

In PHP, the `array` type is commonly used to represent three different data structures:

[List](https://en.wikipedia.org/wiki/List_(abstract_data_type)):
```php
$a = [1, 2, 3, 4, 5];
```

[Associative array](https://en.wikipedia.org/wiki/Associative_array):  
```php
$a = [0 => 'hello', 5 => 'goodbye'];
$b = ['a' => 'AA', 'b' => 'BB', 'c' => 'CC']
```

Makeshift [Structs](https://en.wikipedia.org/wiki/Struct_(C_programming_language)):
```php
$a = ['name' => 'Psalm', 'type' => 'tool'];
```

PHP treats all these arrays the same, essentially (though there are some optimisations under the hood for the first case).

#### PHPDoc syntax

PHPDoc [allows you to specify](https://phpdoc.org/docs/latest/references/phpdoc/types.html#arrays) the  type of values the array holds with the annotation:
```php
/** @return ValueType[] */
```

#### Generic arrays

Psalm uses a syntax [borrowed from Java](https://en.wikipedia.org/wiki/Generics_in_Java) that allows you denote the types of both keys *and* values:
```php
/** @return array<TKey, TValue> */
```

#### Object-like arrays

Psalm supports a special format for arrays where the key offsets are known: object-like arrays.

Given an array

```php
["hello", "world", "foo" => new stdClass, 28 => false];
```

Psalm will type it internally as:

```
array{0: string, 1: string, foo: stdClass, 28: false}
```

You can specify types in that format yourself, e.g.

```php
/** @return array{foo: string, bar: int} */
```

### Value types

Psalm also allows you to specify values in types.

#### null

This is the `null` value, destroyer of worlds. Use it sparingly. Psalm supports you writing `?Foo` to mean `null|Foo`.

#### no-return

`no-return` is the 'return type' for a function that can never actually return, such as `die()`, `exit()`, or a function that
always throws an exception. It may also be written as `never-return` or `never-returns`, and  is also known as the *bottom type*.

#### true, false

Use of `true` and `false` is also PHPDoc-compatible

#### "some_string", 4, 3.14

Psalm also allows you specify literal values in types, e.g. `@return "good"|"bad"`

#### Regular class constants

Psalm allows you to include class constants in types, e.g. `@return Foo::GOOD|Foo::BAD`. You can also specify explicit class strings e.g. `Foo::class|Bar::class`

If you want to specify that a parameter should only take class strings that are, or extend, a given class, you can use the annotation `@param class-string<Foo> $foo_class`. If you only want the param to accept that exact class string, you can use the annotation `Foo::class`:

```php
<?php
class A {}
class AChild extends A {}
class B {}
class BChild extends B {}

/**
 * @param class-string<A>|class-string<B> $s
 */
function foo(string $s) : void {}

/**
 * @param A::class|B::class $s
 */
function bar(string $s) : void {}

foo(A::class); // works
foo(AChild::class); // works
foo(B::class); // works
foo(BChild::class); // works
bar(A::class); // works
bar(AChild::class); // fails
bar(B::class); // works
bar(BChild::class); // fails
```

### Callable types

Psalm supports a special format for `callable`s of the form. It can also be used for annotating `Closure`.

```
callable(Type1, OptionalType2=, ...SpreadType3):ReturnType
```

Adding `=` after the type implies it is optional, and prefixing with `...` implies the use of the spread operator.

Using this annotation you can specify that a given function return a `Closure` e.g.

```php
/**
 * @return Closure(bool):int
 */
function delayedAdd(int $x, int $y) : Closure {
  return function(bool $debug) use ($x, $y) {
    if ($debug) echo "got here" . PHP_EOL;
    return $x + $y;
  };
}

$adder = delayedAdd(3, 4);
echo $adder(true);
```

### Parameterised types

Psalm supports the use of parameterised types with the use of [`@template` tags](templated_annotations.md).
