# Typing in Psalm

Psalm is able to interpret all PHPDoc type annotations, and use them to further understand the codebase.

## Union Types

PHP and other dynamically-typed languages allow expressions to resolve to conflicting types – for example, after this statement
```php
$rabbit = rand(0, 10) === 4 ? 'rabbit' : ['rabbit'];
```
`$rabbit` will be either a `string` or an `array`. We can represent that idea with Union Types – so `$rabbit` is typed as `string|array`. Union types represent *all* the possible types a given variable can have.

### Use of `false` in Union Types

This also extends to builtin PHP methods, many of which can return `false` to denote some sort of failure. For example, `strpos` has the return type `int|false`. This is a more specific version of `int|bool`, and allows us to evaluate logic like
```php
function str_index_of(string $haystack, string $needle) : int {
  $pos = strpos($haystack, $needle);
  if ($pos === false) {
    return -1;
  }
  return $pos;
}
```
and verify that `str_index_of` *always* returns an integer. If we instead typed the return of `strpos` as `int|bool`, then according to Psalm the last statement `return $pos` could return either an integer or `true` (the solution would be to turn `if ($pos === false)` into `if (is_bool($pos))`.

## Property declaration types vs Assignment typehints

You can use the `/** @var Type */` docblock to annotate both [property declarations](http://php.net/manual/en/language.oop5.properties.php) and to help Psalm understand variable assignment.

### Property declaration types

You can specify a particular type for a class property declarion in Psalm by using the `@var` declaration:

```php
/** @var string|null */
public $foo;
```

When checking `$this->foo = $some_variable;`, Psalm will check to see whether `$some_variable` is either `string` or `null` and, if neither, emit an issue.

If you leave off the property type docblock, Psalm will emit a `MissingPropertyType` issue.

### Assignment typehints

Consider the following code:

```php
namespace YourCode {
  function bar() : int {
    $a = \ThirdParty\foo();
    return $a;
  }
}
namespace ThirdParty {
  function foo() {
    return mt_rand(0, 100);
  }
}
```

Psalm does not know what the third-party function `ThirdParty\foo` returns, because the author has not added any return types. If you know that the function returns a given value you can use an assignment typehint like so:

```php
namespace YourCode {
  function bar() : int {
    /** @var int */
    $a = \ThirdParty\foo();
    return $a;
  }
}
namespace ThirdParty {
  function foo() {
    return mt_rand(0, 100);
  }
}
```

This tells Psalm that `int` is a possible type for `$a`, and allows it to infer that `return $a;` produces an integer.

Unlike property types, however, assignment typehints are not binding – they can be overridden by a new assignment without Psalm emitting an issue e.g.

```php
/** @var string|null */
$a = foo();
$a = 6; // $a is now typed as an int
```

You can also use typehints on specific variables e.g.

```php
/** @var string $a */
echo strpos($a, 'hello');
```

This tells Psalm to assume that `$a` is a string (though it will still throw an error if `$a` is undefined).

### Typing arrays

In PHP, the `array` type is commonly used to represent three different data structures:

**[List](https://en.wikipedia.org/wiki/List_(abstract_data_type))**:

```php
$a = [1, 2, 3, 4, 5];
```

**[Associative array](https://en.wikipedia.org/wiki/Associative_array)**

```php
$a = [0 => 'hello', 5 => 'goodbye'];
$b = ['a' => 'AA', 'b' => 'BB', 'c' => 'CC']
```

**Makeshift [Structs](https://en.wikipedia.org/wiki/Struct_(C_programming_language))**

```php
$a = ['name' => 'Psalm', 'type' => 'tool'];
```

PHP treats all these arrays the same, essentially (though there are some optimisations under the hood for the first case).

PHPDoc [allows you to specify](https://phpdoc.org/docs/latest/references/phpdoc/types.html#arrays) the  type of values the array holds with the annotation:
```php
/** @return TValue[] */
```

where `TValue` is a union type, but it does not allow you to specify the type of keys.

Psalm uses a syntax [borrowed from Java](https://en.wikipedia.org/wiki/Generics_in_Java) to denote the types of both keys *and* values:
```php
/** @return array<TKey, TValue> */
```

## Object-like Arrays

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

### Backwards compatibility

Psalm fully supports PHPDoc's array typing syntax, such that any array typed with `TValue[]` will be typed in Psalm as `array<mixed, TValue>`. That also extends to generic type definitions with only one param e.g. `array<TValue>`, which is equivalent to `array<mixed, TValue>`.

Psalm supports PHPDoc’s [type syntax](https://docs.phpdoc.org/guides/types.html), and also the [proposed PHPDoc PSR type syntax](https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md#appendix-a-types).

## Class constants

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

## Callables and Closures

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

## Specifying string/int options (aka enums)

Psalm allows you to specify a specific set of allowed string/int values for a given function or method.

Whereas this would cause Psalm to [complain that not all paths return a value](https://getpsalm.org/r/9f6f1ceab6):

```php
function foo(string $s) : string {
  switch ($s) {
    case 'a':
      return 'hello';

    case 'b':
      return 'goodbye';
  }
}
```

If you specify the param type of `$s` as `'a'|'b'` Psalm will know that all paths return a value:

```php
/**
 * @param 'a'|'b' $s
 */
function foo(string $s) : string {
  switch ($s) {
    case 'a':
      return 'hello';

    case 'b':
      return 'goodbye';
  }
}
```

You can also wrap the options in parentheses - `('a' | 'b')` - if you like to space things out.

If the values are in class constants, you can use those too:

```php
class A {
  const FOO = 'foo';
  const BAR = 'bar';
}

/**
 * @param (A::FOO | A::BAR) $s
 */
function foo(string $s) : string {
  switch ($s) {
    case A::FOO:
      return 'hello';

    case A::BAR:
      return 'goodbye';
  }
}
```
