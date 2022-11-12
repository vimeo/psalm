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

## Array shapes

Psalm supports a special format for arrays where the key offsets are known: array shapes, also known as "object-like arrays".

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

Tip: if you find yourself copying the same complex array shape over and over again to avoid `InvalidArgument` issues, try using [type aliases](utility_types.md#type-aliases), instead.

### Validating array shapes

Use [Valinor](https://github.com/CuyZ/Valinor) in strict mode to easily assert array shapes at runtime using Psalm array shape syntax (instead of manually asserting keys with isset):

```php
try {
  $array = (new \CuyZ\Valinor\MapperBuilder())
      ->mapper()
      ->map(
          'array{a: string, b: int}',
          json_decode(file_get_contents('https://.../'), true)
      );

  /** @psalm-trace $array */; // array{a: string, b: int}

  echo $array['a'];
  echo $array['b'];
} catch (\CuyZ\Valinor\Mapper\MappingError $error) {
  // Do something…
}
```

Valinor provides both runtime and static Psalm assertions with full Psalm syntax support and many other features, check out the [Valinor documentation](https://valinor.cuyz.io/latest/) for more info!

## List shapes

Starting in Psalm 5, Psalm also supports a special format for list arrays where the key offsets are known.

Given a list array

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

List shapes are essentially n-tuples [from a type theory perspective](https://en.wikipedia.org/wiki/Tuple#Type_theory).


## Unsealed array and list shapes

Starting from Psalm v5, array shapes and list shapes can be marked as open by adding `...` as their last element.

Here we have a function `handleOptions` that takes an array of options. The type tells us it has a single known key with type `string`, and potentially many other keys of unknown types.


```php
/** @param array{verbose: string, ...} $options */
function handleOptions(array $options): float {
    if ($options['verbose']) {
        var_dump($options);
    }
}

$options = get_opt(/* some code */);
$options['verbose'] = isset($options['verbose']);
handleOptions($options);
```

## Callable arrays

An array holding a callable, like PHP's native `call_user_func()` and friends supports it:

```php
<?php

$callable = ['myClass', 'aMethod'];
$callable = [$object, 'aMethod'];
```

## non-empty-array

An array which is not allowed to be empty.
[Generic syntax](#generic-arrays) is also supported: `non-empty-array<string, int>`.
