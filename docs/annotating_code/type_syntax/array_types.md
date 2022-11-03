# Array types

In PHP, the `array` type is commonly used to represent three different data structures:

[List](https://en.wikipedia.org/wiki/List_(abstract_data_type)):
```php
<?php
$a = [1, 2, 3, 4, 5];
```

[Associative array](https://en.wikipedia.org/wiki/Associative_array):  
```php
<?php
$a = [0 => 'hello', 5 => 'goodbye'];
$b = ['a' => 'AA', 'b' => 'BB', 'c' => 'CC']
```

Makeshift [Structs](https://en.wikipedia.org/wiki/Struct_(C_programming_language)):
```php
<?php
$a = ['name' => 'Psalm', 'type' => 'tool'];
```

PHP treats all these arrays the same, essentially (though there are some optimisations under the hood for the first case).

Psalm has a few different ways to represent arrays in its type system:

## Generic arrays

Psalm uses a syntax [borrowed from Java](https://en.wikipedia.org/wiki/Generics_in_Java) that allows you to denote the types of both keys *and* values:
```php
/** @return array<TKey, TValue> */
```

You can also specify that an array is non-empty with the special type `non-empty-array<TKey, TValue>`.

### PHPDoc syntax

PHPDoc [allows you to specify](https://docs.phpdoc.org/latest/guide/references/phpdoc/types.html#arrays) the  type of values a generic array holds with the annotation:
```php
/** @return ValueType[] */
```

In Psalm this annotation is equivalent to `@psalm-return array<array-key, ValueType>`.

Generic arrays encompass both _associative arrays_ and _lists_.

## Lists

(Psalm 3.6+)

Psalm supports a `list` type that represents continuous, integer-indexed arrays like `["red", "yellow", "blue"]`.

A frequent way to create a list is with the `$arr[] =` notation.

These arrays will return true to `array_is_list($arr)`(PHP 8.1+) and represent a large percentage of all array usage in PHP applications.

A `list` type is of the form `list<SomeType>`,  where `SomeType` is any permitted [union type](union_types.md) supported by Psalm.

- `list` is a subtype of `array<int, mixed>`
- `list<Foo>` is a subtype of `array<int, Foo>`.

List types show their value in a few ways:

```php
<?php
/**
 * @param array<int, string> $arr
 */
function takesArray(array $arr) : void {
  if ($arr) {
     // this index may not be set
    echo $arr[0];
  }
}

/**
 * @psalm-param list<string> $arr
 */
function takesList(array $arr) : void {
  if ($arr) {
    // list indexes always start from zero,
    // so a non-empty list will have an element here
    echo $arr[0];
  }
}

takesArray(["hello"]); // this is fine
takesArray([1 => "hello"]); // would trigger bug, without warning

takesList(["hello"]); // this is fine
takesList([1 => "hello"]); // triggers warning in Psalm
```

## Object-like arrays

Psalm supports a special format for arrays where the key offsets are known: object-like arrays, also known as **array shapes**.

Given an array

```php
<?php
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

Optional keys can be denoted by a trailing `?`, e.g.:

```php
/** @return array{optional?: string, bar: int} */
```

Starting from Psalm v5, object-like arrays created from literals and phpdocs are sealed by default, which means Psalm can reason a lot better about them, see [here](#sealed-object-like-arrays) for more info.  

## Object-like lists

Psalm supports a special format for lists where the key offsets are known: object-like lists, also known as **list shapes**.

Given a list

```php
<?php
["hello", "world", new stdClass, false];
```

Psalm will type it internally as:

```
list{string, string, stdClass, false}
```

You can specify types in that format yourself, e.g.

```php
/** @return list{string, int} */
/** @return list{0: string, 1: int} */
```

Optional keys can be denoted by a specifying keys for all elements and specifying a trailing `?` for optional keys, e.g.:

```php
/** @return list{0: string, 1?: int} */
```

Starting from Psalm v5, object-like lists created from literals and phpdocs are sealed by default, which means Psalm can reason a lot better about them, see [here](#sealed-object-like-arrays) for more info.  

## Callable arrays

An array holding a callable, like PHP's native `call_user_func()` and friends supports it:

```php
<?php

$callable = ['myClass', 'aMethod'];
$callable = [$object, 'aMethod'];
```

## Sealed object-like arrays

Starting from Psalm v5, object-like arrays and lists created from literals and phpdocs are sealed by default.
Sealed arrays can only have _only_ have the keys specified in the shape: extra keys are forbidden.  

This additional simplicity means Psalm can reason a lot better about them:  

```php
/** @var array{foo: string, bar?: int, baz?: float} $arr */

if (count($arr) > 2) {
  echo $arr['baz']; // OK!
}

// Off by one bug, detected by Psalm:
if (count($arr) > 3) {
  // DocblockTypeContradiction - Docblock-defined type ...
}

/** @param array{0: float, 1: float, 2?: float} $arr */
function avgShape(array $arr): float {
  return array_sum($arr) / count($arr);
}

// InvalidArgument - Argument 1 of avgShape expects ...
avgShape([123.1, 321.0, 1.0, new class {}, 'test']);
```

The above examples contain bugs which can be detected by Psalm *only when using sealed arrays*.  

The counterpart to sealed arrays are [unsealed arrays &rauo;](#unsealed-object-like-arrays), generated as intermediate types when asserting raw arrays.  
Unsealed arrays are by definition uncertain, so Psalm can't reason well about them: always convert them to sealed arrays as specified [here &raquo;](#unsealed-object-like-arrays).  

## Unsealed object-like arrays

Starting from v5, Psalm defines a supertype of object-like arrays called unsealed object-like arrays.  
This type is used in cases where an [object-like array](#object-like-arrays) may have extra keys not specified in the shape.  
Avoid using unsealed arrays in your codebase, as **they can cause undetectable bugs**: always transform them into sealed arrays before use.  
Unsealed arrays are by definition uncertain, so, unlike [sealed arrays](#sealed-object-like-arrays), Psalm can't reason well about them: always use sealed arrays in your PHPDocs.  

Here's how unsealed arrays can cause weird bugs:

```php
<?php

/**
 * @param unsealed-array{a: float, b: float} $params
 */
function avg(array $params): float {
  return array_sum($params) / 2.0;
}

$arr = json_decode(file_get_contents('https://.../'), true);

if (is_array($arr)
  && isset($arr['a']) && is_float($arr['a'])
  && isset($arr['b']) && is_float($arr['b'])
) {
  /** @psalm-trace $array */; // unsealed-array{a: float, b: float}
  echo avg($arr);
}

/**
 * @param array{a: float, b: float, c: float} $params
 */
function avgCoefficient(array $params): float {
  return avg($params) * $params['c'];
}
```

In this example, we assume that `avg` takes an array with two elements, but what happens if the API (or some other function) also provides a third `c` parameter to a function that averages two elements?  
And what would happen if a string `csrf` parameter were provided in `$_POST`?  

Clearly, just asserting the shape of the array is not enough: we need to guarantee that the array will contain only the array elements we need.  

```php
<?php

/**
 * @param unsealed-array{a: float, b: float} $params
 */
function avg(array $params): float {
  return array_sum($params) / 2.0;
}

$arr = json_decode(file_get_contents('https://.../'), true);

if (is_array($arr)
  && isset($arr['a']) && is_float($arr['a'])
  && isset($arr['b']) && is_float($arr['b'])
  && count($arr) === 2 // <-- Ensure only two elements are present
) {
  /** @psalm-trace $array */; // array{a: float, b: float}
  echo avg($arr);
}

/**
 * @param array{a: float, b: float, c: float} $params
 */
function avgCoefficient(array $params): float {
  // InvalidArgument - Argument 1 of avg expects array{a: float, b: float}, but array{a: float, b: float, c: float} provided
  //return avg($params) * $params['c'];

  $coefficient = $params['c'];
  unset($params['c']);
  return avg($params) * $coefficient;
}
```

You can also manually provide a `['a' => $arr['a'], 'b' => $arr['b']]`, but there's an even better way to seamlessly validate user-provided input:  

Use [Valinor](https://github.com/CuyZ/Valinor) in strict mode to easily assert sealed arrays @ runtime using Psalm array shape syntax (instead of manually asserting keys with isset):

```php
try {
  $array = (new \CuyZ\Valinor\MapperBuilder())
      ->mapper()
      ->map(
          'array{a: string, b: int}',
          json_decode(file_get_contents('https://.../'), true);
      );

  /** @psalm-trace $array */; // array{a: string, b: int}

  echo $array['a'];
  echo $array['b'];
} catch (\CuyZ\Valinor\Mapper\MappingError $error) {
  // Do something…
}
```

Valinor provides both runtime and static Psalm assertions with full Psalm syntax support and many other features, check out the [Valinor documentation](https://valinor.cuyz.io/latest/) for more info!  

## non-empty-array

An array which is not allowed to be empty.
