# Utility types

Psalm supports some _magical_ utility types that brings superpower to the PHP type system.

## `key-of<T>`

(Psalm 5.0+)

The `key-of` utility returns the offset-type for any [array type](array_types.md).

Some examples:
- `key-of<Foo\Bar::ARRAY_CONST>` evaluates to offset-type of `ARRAY_CONST` (Psalm 3.3+)
- `key-of<list<mixed>>` evaluates to `int`
- `key-of<array{a: mixed, b: mixed}|array{c: mixed}>` evaluates to `'a'|'b'|'c'`
- `key-of<string[]>` evaluates to `array-key`
- `key-of<T>` evaluates to the template param's offset-type (ensure `@template T of array`)

### Notes on template usage

If you use `key-of` with a template param, you can fulfill the type check only with these allowed methods:
- `array_keys($t)`
- `array_key_first($t)`
- `array_key_last($t)`

Currently `array_key_exists($key, $t)` **does not** infer that `$key` is of `key-of<T>`.

```php
/**
 * @template T of array
 * @param T $array
 * @return list<key-of<T>>
 */
function getKeys($array) {
    return array_keys($array);
}
```


## `value-of<T>`

(Psalm 5.0+)

The `value-of` utility returns the value-type for any [array type](array_types.md).

Some examples:
- `value-of<Foo\Bar::ARRAY_CONST>` evaluates to value-type of `ARRAY_CONST` (Psalm 3.3+)
- `value-of<list<float>>` evaluates to `float`
- `value-of<array{a: bool, b: int}|array{c: string}>` evaluates to `bool|int|string`
- `value-of<string[]>` evaluates to `string`
- `value-of<T>` evaluates to the template param's value-type (ensure `@template T of array`)

### Notes on template usage

If you use `value-of` with a template param, you can fulfill the type check only with these allowed methods:
- `array_values`

```php
/**
 * @template T of array
 * @param T $array
 * @return value-of<T>[]
 */
function getValues($array) {
    return array_values($array);
}
```

Currently `in_array($value, $t)` **does not** infer that `$value` is of `value-of<T>`.


## `properties-of<T>`

(Psalm 5.0+)

This collection of _utility types_ construct a keyed-array type, with the names of non-static properties of a class as
keys, and their respective types as values. This can be useful if you need to convert objects into arrays.

```php
class A {
  public string $foo = 'foo!';
  public int $bar = 42;

  /**
   * @return properties-of<self>
   */
  public function asArray(): array {
    return [
      'foo' => $this->foo,
      'bar' => $this->bar,
    ];
  }

  /**
   * @return list<key-of<properties-of<self>>>
   */
  public function attributeNames(): array {
    return ['foo', 'bar']
  }
}
```

### Variants

Note that `properties-of<T>` will return **all non-static** properties. There are the following subtypes to pick only
properties with a certain visibility:
- `public-properties-of<T>`
- `protected-properties-of<T>`
- `private-properties-of<T>`


### Sealed array support

Use final classes if you want to properties-of and get_object_vars to return sealed arrays:

```php
/**
 * @template T
 * @param T $object
 * @return properties-of<T>
 */
function asArray($object): array {
  return get_object_vars($object);
}


class A {
  public string $foo = 'foo!';
  public int $bar = 42;
}

final class B extends A {
  public float $baz = 2.1;
}

$a = asArray(new A);
/** @psalm-trace $a */; // array{foo: string, bar: int, ...}

$b = asArray(new B);
/** @psalm-trace $b */; // array{foo: string, bar: int, baz: float}
```

## `class-string-map<T as Foo, T>`

Used to indicate an array where each value is equal an instance of the class string contained in the key:

```php
<?php

/**
 * @psalm-consistent-constructor
 */
class Foo {}

/**
 * @psalm-consistent-constructor
 */
class Bar extends Foo {}

class A {
  /** @var class-string-map<T as Foo, T> */
  private static array $map = [];

  /**
   * @template U as Foo
   * @param class-string<U> $class
   * @return U
   */
  public static function get(string $class) : Foo {
    if (isset(self::$map[$class])) {
      return self::$map[$class];
    }

    self::$map[$class] = new $class();
    return self::$map[$class];
  }
}

$foo = A::get(Foo::class);
$bar = A::get(Bar::class);

/** @psalm-trace $foo */; // Foo
/** @psalm-trace $bar */; // Bar
```

If we had used an `array<class-string<Foo>, Foo>` instead of a `class-string-map<T as Foo, T>` in the above example, we would've gotten some false positive `InvalidReturnStatement` issues, caused by the lack of a type assertion inside the `isset`.  
On the other hand, when using `class-string-map`, Psalm assumes that the value obtained by using a key `class-string<T>` is always equal to `T`.  

Unbounded templates can also be used for unrelated classes:

```php
<?php

/**
 * @psalm-consistent-constructor
 */
class Foo {}

/**
 * @psalm-consistent-constructor
 */
class Bar {}

/**
 * @psalm-consistent-constructor
 */
class Baz {}

class A {
  /** @var class-string-map<T, T> */
  private static array $map = [];

  /**
   * @template U
   * @param class-string<U> $class
   * @return U
   */
  public static function get(string $class) : object {
    if (isset(self::$map[$class])) {
      return self::$map[$class];
    }

    self::$map[$class] = new $class();
    return self::$map[$class];
  }
}

$foo = A::get(Foo::class);
$bar = A::get(Bar::class);
$baz = A::get(Baz::class);

/** @psalm-trace $foo */; // Foo
/** @psalm-trace $bar */; // Bar
/** @psalm-trace $baz */; // Baz
```

## `T[K]`

Used to get the value corresponding to the specified key:

```php
<?php

/**
 * @template T as array
 * @template TKey as string
 * @param T $arr
 * @param TKey $k
 * @return T[TKey]
 */
function a(array $arr, string $k): mixed {
  assert(isset($arr[$k]));
  return $arr[$k];
}

$a = a(['test' => 123], 'test');
/** @psalm-trace $a */; // 123
```

## Type aliases

Psalm allows defining type aliases for complex types (like array shapes) which must be reused often:

```php
/**
 * @psalm-type PhoneType = array{phone: string}
 */
class Phone {
    /**
     * @psalm-return PhoneType
     */
    public function toArray(): array {
        return ["phone" => "Nokia"];
    }
}
```

You can use the [`@psalm-import-type`](../supported_annotations.md#psalm-import-type) annotation to import a type defined with [`@psalm-type`](../supported_annotations.md#psalm-type) if it was defined somewhere else.

```php
<?php
/**
 * @psalm-import-type PhoneType from Phone
 */
class User {
    /**
     * @psalm-return PhoneType
     */
    public function toArray(): array {
        return array_merge([], (new Phone())->toArray());
    }
}
```

You can also alias a type when you import it:

```php
<?php
/**
 * @psalm-import-type PhoneType from Phone as MyPhoneTypeAlias
 */
class User {
    /**
     * @psalm-return MyPhoneTypeAlias
     */
    public function toArray(): array {
        return array_merge([], (new Phone())->toArray());
    }
}
```

## Variable templates

Variable templates allow directly using variables instead of template types, for example instead of the following verbose example:

```php
<?php

/**
 * @template TA as string
 * @template TB as string
 * @template TChoose as bool
 * @param TA $a
 * @param TB $b
 * @param TChoose $choose
 * @return (TChoose is true ? TA : TB)
 */
function pick(string $a, string $b, bool $choose): string {
  return $choose ? $a : $b;
}

$a = pick('a', 'b', true);
/** @psalm-trace $a */; // 'a'

$a = pick('a', 'b', false);
/** @psalm-trace $a */; // 'b'
```

We can instead use variable templates like so:

```php
<?php

/**
 * @return ($choose is true ? $a : $b)
 */
function pick(string $a, string $b, bool $choose): string {
  return $choose ? $a : $b;
}

$a = pick('a', 'b', true);
/** @psalm-trace $a */; // 'a'

$a = pick('a', 'b', false);
/** @psalm-trace $a */; // 'b'
```
