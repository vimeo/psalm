# Object types

## Unnamed objects

`object` are examples of unnamed object types. This type is also a valid type in PHP.

## Named objects

`stdClass`, `Foo`, `Bar\Baz` etc. are examples of named object types. These types are also valid types in PHP.

## Object properties

Psalm supports specifying the properties of an object and their expected types, e.g.:

```php
/** @param object{foo: string} $obj */
function takesObject(object $obj) : string {
    return $obj->foo;
}

takesObject((object) ["foo" => "hello"]);
```

Optional properties can be denoted by a trailing `?`, e.g.:

```php
/** @param object{optional?: string} */
```

## Generic object types

Psalm supports using generic object types like `ArrayObject<int, string>`. Any generic object should be typehinted with appropriate [`@template` tags](../templated_annotations.md).

## Generators

Generator types support up to four parameters, e.g. `Generator<int, string, mixed, void>`:

1. `TKey`, the type of the `yield` key - default: `mixed`
2. `TValue`, the type of the `yield` value - default: `mixed`
3. `TSend`, the type of the `send()` method's parameter - default: `mixed`
4. `TReturn`, the return type of the `getReturn()` method - default: `mixed`

`Generator<int>` is a shorthand for `Generator<mixed, int, mixed, mixed>`.

`Generator<int, string>` is a shorthand for `Generator<int, string, mixed, mixed>`.
