# Utility types

Psalm supports some _magical_ utility types that brings superpower to the PHP type system.

## `properties-of<T>`

This collection of _utility types_ construct a keyed-array, with the names of non-static properties of a class as keys,
and their respective types as values. This can be useful if you need to convert objects into arrays.

```php
class A {
  public string $foo;
  public int $bar;

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
