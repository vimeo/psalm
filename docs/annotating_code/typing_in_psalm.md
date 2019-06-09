# Typing in Psalm

Psalm is able to interpret all PHPDoc type annotations, and use them to further understand the codebase.

Types are used to describe acceptable values for properties, variables, function parameters and `return $x`.

## [Docblock Type Syntax](docblock_type_syntax.md)

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

### Backwards compatibility

Psalm fully supports PHPDoc's array typing syntax, such that any array typed with `TValue[]` will be typed in Psalm as `array<mixed, TValue>`. That also extends to generic type definitions with only one param e.g. `array<TValue>`, which is equivalent to `array<mixed, TValue>`.

Psalm supports PHPDoc’s [type syntax](https://docs.phpdoc.org/guides/types.html), and also the [proposed PHPDoc PSR type syntax](https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md#appendix-a-types).


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
