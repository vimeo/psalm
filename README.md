<h1><img src="PsalmLogo.png" height="64" alt="logo" /></h1>

Inspects your code and finds errors

...

## Checking non-PHP files (e.g. templates)

Psalm supports the ability to check various PHPish files by extending the `FileChecker` class. For example, if you have a template where the variables are set elsewhere, Psalm can scrape those variables and check the template with those variables pre-populated.

An example TemplateChecker is provided [here](examples/TemplateChecker.php).

To ensure your custom `FileChecker` is used, you must update the Psalm `fileExtensions` config in psalm.xml:
```xml
<fileExtensions>
    <extension name=".php" />
    <extension name=".phpt" filetypeHandler="path/to/TemplateChecker.php" />
</fileExtensions>
```

## Typing in Psalm

### Property types vs Assignment typehints

You can use the `/** @var Type */` docblock to annotate both property declarations and to help Psalm understand variable assignment.

#### Property types

You can specify a particular type for an class property in Psalm by using the `@var` declaration:

```php
/** @var string|null */
public $foo;
```

When checking `$this->foo = $some_variable;`, Psalm will check to see whether `$some_variable` is either `string` or `null` and, if neither, emit an issue.

#### Assignment typehints

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

Because Psalm scans a file progressively, it cannot tell that `return $a` produces an integer. Instead it returns knows only that `$a` is not `empty`. We can fix this by adding a type hint docblock:

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
