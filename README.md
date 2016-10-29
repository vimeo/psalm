<h1><img src="PsalmLogo.png" height="64" alt="logo" /></h1>

Inspects your code and finds errors

...

Because it's based on nikic's PhpParser, it supports @var declarations on lines where etsy/phan does not. Yay.

### Typing arrays

In PHP, the `array` type is commonly used to represent three different data structures:
 - a [List](https://en.wikipedia.org/wiki/List_(abstract_data_type))
   
   ```php
   $a = [1, 2, 3, 4, 5];
   ```
 - an [Associative array](https://en.wikipedia.org/wiki/Associative_array)
   
   ```php
   $a = [0 => 'hello', 5 => 'goodbye'];
   $a = ['a' => 'AA', 'b' => 'BB', 'c' => 'CC']
   ```
 - makeshift [Structs](https://en.wikipedia.org/wiki/Struct_(C_programming_language))
   
   ```php
   $a = ['name' => 'Psalm', 'type' => 'tool'];
   ```

PHP treats all these arrays the same, essentially (though there are some optimisations under the hood for the first case).

PHPDoc [allows you to specify](https://phpdoc.org/docs/latest/references/phpdoc/types.html#arrays) the  type of values the array holds with the anootation:
```php
/** @return TValue[] */
```

where `TValue` is a union type, but it does not allow you to specify the type of keys.

Psalm uses a syntax [borrowed from Java](https://en.wikipedia.org/wiki/Generics_in_Java) to denote the types of both keys *and* values:
```php
/** @return array<TKey, TValue> */
```

#### Makeshift Structs

Ideally (in the author's opinion), all data would either be encoded as lists, associative arrays, or as well-defined objects. However, PHP arrays are often used as makeshift structs.

Hack (by Facebook) supports this usage by way of the [Shape datastructure](https://docs.hhvm.com/hack/shapes/introduction), but there is no agreed-upon documentation format for such arrays in regular PHP-land.

Psalm solves this by adding another way annotate array types, by using an object-like syntax when describing them.

So, for instance,
```php
$a = ['name' => 'Psalm', 'type' => 'tool']; // 
```
is assigned the type `array{ name: string, type: string}`.

#### Backwards compatibility

Psalm fully supports PHPDoc's array typing syntax, such that any array typed with `TValue[]` will be typed in Psalm as `array<mixed, TValue>`. That also extends to generic type definitions with only one param e.g. `array<TValue>`, which is equivalent to `array<mixed, TValue>`.
