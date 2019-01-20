# Supported docblock annotations

Psalm supports a wide range of docblock annotations.

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

### `@param-out`

This is used to specify that a by-ref type is different from the one that entered. In the function below the first param can be null, but once the function has executed the by-ref value is not null.

```php
/**
 * @param-out string $s
 */
function addFoo(?string &$s) : void {
    if ($s === null) {
        $s = "hello";
    }
    $s .= "foo";
}
```

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

### `@psalm-assert`, `@psalm-assert-if-true` and `@psalm-assert-if-false`

These annotations allow you to specify very basic facts about how a class of functions operate.

For example, if you have a class that verified its input is an array of strings, you can make that clear to Psalm:

```php
/** @psalm-assert string[] $arr */
function validateStringArray(array $arr) : void {
    foreach ($arr as $s) {
        if (!is_string($s)) {
          throw new UnexpectedValueException('Invalid value ' . gettype($s));
        }
    }
}
```

This enables you to call the `validateStringArray` function on some data and have Psalm understand that the given data *must* be an array of strings:

```php
function takesString(string $s) : void {}
function takesInt(int $s) : void {}

function takesArray(array $arr) : void {
    takesInt($arr[0]); // this is fine

    validateStringArray($arr);

    takesInt($arr[0]); // this is an error

    foreach ($arr as $a) {
        takesString($a); // this is fine
    }
}
```

Similarly, `@psalm-assert-if-true` and `@psalm-assert-if-false` will filter input if the function/method returns `true` and `false` respectively:

```php
class A {
    public function isValid() : bool {
        return (bool) rand(0, 1);
    }
}
class B extends A {
    public function bar() : void {}
}

/**
 * @psalm-assert-if-true B $a
 */
function isValidB(A $a) : bool {
    return $a instanceof B && $a->isValid();
}

/**
 * @psalm-assert-if-false B $a
 */
function isInvalidB(A $a) : bool {
    return $a instanceof B || !$a->isValid();
}

function takesA(A $a) : void {
    if (isValidB($a)) {
        $a->bar();
    }

    if (isInvalidB($a)) {
        // do something
    } else {
        $a->bar();
    }

    $a->bar(); //error
}
```

As well as getting Psalm to understand that the given data must be a certain type, you can also show that a variable must be not null:

```php

/**
 * @psalm-assert !null $value
 */
function assertNotNull($value): void {
  // Some check that will mean the method will only complete if $value is not null.
}

```

And you can check on null values:

```php

/**
 * @psalm-assert-if-true null $value
 */
function isNull($value): bool {
  return ($value === null);
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

A detailed write-up is found in [Typing in Psalm](typing_in_psalm.md)

