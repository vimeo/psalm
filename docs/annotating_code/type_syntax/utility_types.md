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


### Limited template support

As there is no way to statically analyze if a method returns all properties of a generic param (e.g. via Reflection or
serialization), you have to annotate it where you assume it.

```php
/**
 * @param T $object
 * @return properties-of<T>
 */
public function asArray($object): array {
  /** @var properties-of<T> */
  $array = json_decode(json_encode($object), true);
  return $array;
}


class A {
  public string $foo = 'foo!';
  public int $bar = 42;
}

$a = new A();
$aAsArray = asArray($a);
$aAsArray['foo']; // valid
$aAsArray['adams']; // error!
```
