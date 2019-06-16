# Array types

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

## PHPDoc syntax

PHPDoc [allows you to specify](https://phpdoc.org/docs/latest/references/phpdoc/types.html#arrays) the  type of values the array holds with the annotation:
```php
/** @return ValueType[] */
```

## Generic arrays

Psalm uses a syntax [borrowed from Java](https://en.wikipedia.org/wiki/Generics_in_Java) that allows you denote the types of both keys *and* values:
```php
/** @return array<TKey, TValue> */
```

You can also specify that an array is non-empty with the special type `non-empty-array<TKey, TValue>`.

## Object-like arrays

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
