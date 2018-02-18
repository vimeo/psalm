# Typing in Psalm

Psalm is able to interpret all PHPDoc type annotations, and use them to further understand the codebase.

## Union Types

PHP and other dynamically-typed languages allow expressions to resolved to conflicting types – for example, after this statement
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
$a = null;

foreach ([1, 2, 3] as $i) {
  if ($a) {
    return $a;
  }
  else {
    $a = $i;
  }
}
```

Because Psalm scans a file progressively, it cannot tell that `return $a` produces an integer. Instead it knows only that `$a` is not `empty`. We can fix this by adding a type hint docblock:

```php
/** @var int|null */
$a = null;

foreach ([1, 2, 3] as $i) {
  if ($a) {
    return $a;
  }
  else {
    $a = $i;
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
 - a [List](https://en.wikipedia.org/wiki/List_(abstract_data_type))

   ```php
   $a = [1, 2, 3, 4, 5];
   ```
 - an [Associative array](https://en.wikipedia.org/wiki/Associative_array)

   ```php
   $a = [0 => 'hello', 5 => 'goodbye'];
   $b = ['a' => 'AA', 'b' => 'BB', 'c' => 'CC']
   ```
 - makeshift [Structs](https://en.wikipedia.org/wiki/Struct_(C_programming_language))

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

### Makeshift Structs

Ideally (in the author's opinion), all data would either be encoded as lists, associative arrays, or as well-defined objects. However, PHP arrays are often used as makeshift structs.

[Hack](http://hacklang.org/) supports this usage by way of the [Shape datastructure](https://docs.hhvm.com/hack/shapes/introduction), but there is no agreed-upon documentation format for such arrays in regular PHP-land.

Psalm solves this by adding another way annotate array types, by using an object-like syntax when describing them.

So, for instance, the method below returns an array of arrays, both of which have the same keys:
```php
/** @return array<int, array<string, string|bool>> */
function getToolsData() : array {
  return [
    ['name' => 'Psalm',     'type' => 'tool', 'active' => true],
    ['name' => 'PhpParser', 'type' => 'tool', 'active' => true]
  ];
}
```

Using the type annotation for associative arrays, we could evaluate the expression
```php
getToolsData()[0]['name']
```
and Psalm would know that it was had the type `string|bool`.

However, we can provide a more-specific return type by using a brace annotation:
```php
/** @return array<int, array{name: string, type: string, active: bool}> */
function getToolsData() : array {
  return [
    ['name' => 'Psalm',     'type' => 'tool', 'active' => true],
    ['name' => 'PhpParser', 'type' => 'tool', 'active' => true]
  ];
}
```

This time, Psalm can evaluate `getToolsData()[0]['name']` and it knows that the expression evaluates to a string.

### Backwards compatibility

Psalm fully supports PHPDoc's array typing syntax, such that any array typed with `TValue[]` will be typed in Psalm as `array<mixed, TValue>`. That also extends to generic type definitions with only one param e.g. `array<TValue>`, which is equivalent to `array<mixed, TValue>`.
