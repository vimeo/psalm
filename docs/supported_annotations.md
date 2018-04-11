# Supported docblock annotations

Psalm supports a wide range of docblock annotations.

## Table of contents

- [PHPDoc tags](#phpdoc-tags)
  - [Off-label usage of PHPDoc tags](#off-label-usage-of-the-var-tag)
- [Psalm-specific tags](#psalm-specific-tags)
  - [@psalm-var, @psalm-param and @psalm-return](#psalm-var-psalm-param-and-psalm-return)
  - [@psalm-suppress](#psalm-suppress-someissuename)
  - [@psalm-ignore-nullable-return](#psalm-ignore-nullable-return)
  - [@psalm-ignore-falsable-return](#psalm-ignore-falsable-return)
  - [@template and @template-typeof](#template-and-template-typeof)
  - [@psalm-seal-properties](#psalm-seal-properties)
- [Type Syntax](#type-syntax)
  - [Class constants](#class-constants)
  - [Object-like arrays](#object-like-arrays)
  - [Callables and Closures](#callables-and-closures)

## PHPDoc tags

Psalm uses the following PHPDoc tags to understand your code:
- [`@var`](https://docs.phpdoc.org/references/phpdoc/tags/var.html)
  Used for specifying the types of properties and variables
- [`@return`](https://docs.phpdoc.org/references/phpdoc/tags/return.html)
  Used for specifying the return types of functions, methods and closures
- [`@param`](https://docs.phpdoc.org/references/phpdoc/tags/param.html)
  Used for specifying types of parameters passed to functions, methods and closures
- [`@property`](https://docs.phpdoc.org/references/phpdoc/tags/property.html)
  Used to specify what properties can be accessed on an object that uses `__get` and `__set`
- [`@property-read`](https://docs.phpdoc.org/references/phpdoc/tags/property-read.html)
  Used to specify what properties can be read on object that uses `__get`
- [`@property-write`](https://docs.phpdoc.org/references/phpdoc/tags/property-write.html)
  Used to specify what properties can be written on object that uses `__set`
- [`@deprecated`](https://docs.phpdoc.org/references/phpdoc/tags/deprecated.html)
  Used to mark functions, methods, classes and interfaces as being deprecated

### Off-label usage of the `@var` tag

The `@var` tag is supposed to only be used for properties. Psalm, taking a lead from PHPStorm and other static analysis tools, allows its use inline in the form `@var Type [VariableReference]`.

If `VariableReference` is provided, it should be of the form `$variable` or `$variable->property`. If used above an assignment, Psalm checks whether the `VariableReference` matches the variable being assigned. If they differ, Psalm will assign the `Type` to `VariableReference` and use it in the expression below.

If no `VariableReference` is given, the annotation tells Psalm that the right hand side of the expression, whether an assignment or a return, is of type `Type`.

```php
/** @var string */
$a = $_GET['foo'];

/** @var string $b */
$b = $_GET['bar'];

function bat(): string {
    /** @var string */
    return $_GET['bat'];
}
```

## Psalm-specific tags

There are a number of custom tags that determine how Psalm treats your code.

### `@psalm-var`, `@psalm-param` and `@psalm-return`

When specifying types in a format not supported phpDocumentor ([but supported by Psalm](#type-syntax)) you may wish to prepend `@psalm-` to the PHPDoc tag, so as to avoid confusing your IDE. If a `@psalm`-prefixed tag is given, Psalm will use it in place of its non-prefixed counterpart.

### `@psalm-suppress SomeIssueName`

This annotation is used to suppress issues. It can be used in function docblocks, class docblocks and also inline, applying to the following statement.

Function docblock example:

```php
/**
 * @psalm-suppress PossiblyNullOperand
 */
function addString(?string $s) {
    echo "hello " . $s;
}
```

Inline example:

```php
function addString(?string $s) {
    /** @psalm-suppress PossiblyNullOperand */
    echo "hello " . $s;
}
```

### `@psalm-ignore-nullable-return`

This can be used to tell Psalm not to worry if a function/method returns null. It’s a bit of a hack, but occasionally useful for scenarios where you either have a very high confidence of a non-null value, or some other function guarantees a non-null value for that particular code path.

```php
class Foo {}
function takesFoo(Foo $f): void {}

/** @psalm-ignore-nullable-return */
function getFoo(): ?Foo {
  return rand(0, 10000) > 1 ? new Foo() : null;
}

takesFoo(getFoo());
```

### `@psalm-ignore-falsable-return`

This provides the same, but for `false`. Psalm uses this internally for functions like `preg_replace`, which can return false if the given input has encoding errors, but where 99.9% of the time the function operates as expected.

### `@template` and `@template-typeof`

[Phan](https://github.com/phan/phan) first introduced the template annotation to allow classes to implement generic-like features.

Psalm extends this with `@template-typeof` to allow you to type methods that instantiate objects e.g.

```php
/**
 * @template T
 * @template-typeof T $class_name
 * @return T
 */
function instantiator(string $class_name) {
    return new $class_name();
}
```

Psalm also uses `@template` annotations in its stubbed versions of PHP array functions e.g.

```php
/**
 * Takes one array with keys and another with values and combines them
 *
 * @template TKey
 * @template TValue
 *
 * @param array<mixed, TKey> $arr
 * @param array<mixed, TValue> $arr2
 * @return array<TKey, TValue>
 */
function array_combine(array $arr, array $arr2) {}
```

### `@psalm-seal-properties`

If you have a magic property getter/setter, you can use `@psalm-seal-properties` to instruct Psalm to disallow getting and setting any properties not contained in a list of `@property` (or `@property-read`/`@property-write`) annotations.

```php
/**
 * @property string $foo
 * @psalm-seal-properties
 */
class A {
     public function __get(string $name): ?string {
          if ($name === "foo") {
               return "hello";
          }
     }

     public function __set(string $name, $value): void {}
}

$a = new A();
$a->bar = 5; // this call fails
```

## Type Syntax

Psalm supports PHPDoc’s [type syntax](https://docs.phpdoc.org/guides/types.html), and also the [proposed PHPDoc PSR type syntax](https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md#appendix-a-types).

### Class constants

Psalm supports a special meta-type for `MyClass::class` constants.

Given a function with a `string` parameter `$class_name`, you can use the annotation `@param class-string $class_name` to tell Psalm make sure that the function is always called with a `::class` constant in that position.

So given

```php
class A {}

/**
 * @param class-string $s
 */
function takesClassName(string $s) : void {}
```

`takesClassName("A");` would trigger a `TypeCoercion` issue (or a `PossiblyInvalidArgument` issue if [`allowCoercionFromStringToClassConst`](configuration.md#coding-style) was set to `false` in your config), whereas `takesClassName(A::class)` is fine.

### Object-like Arrays

Psalm supports a special format for arrays where the key offsets are known: object-like arrays.

Given an array

```php
["hello", "world", "foo" => new stdClass, 28 => false];
```

Psalm will type it internally as:

```
array{0: string, 1: string, foo: stdClass, 28: false}
```

If you want to be explicit about this, you can use this same format in `@var`, `@param` and `@return` types (or `@psalm-var`, `@psalm-param` and `@psalm-return` if you prefer to keep this special format separate).

```php
function takesInt(int $i): void {}
function takesString(string $s): void {}

/**
 * @param (string|int)[] $arr
 * @psalm-param array{0: string, 1: int} $arr
 */
function foo(array $arr): void {
    takesString($arr[0]);
    takesInt($arr[1]);
}

foo(["cool", 4]); // passes
foo([4, "cool"]); // fails
```
### Callables and Closures

Psalm supports a special format for `callable`s of the form

```
callable(Type1, OptionalType2=, ...SpreadType3):ReturnType
```

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

