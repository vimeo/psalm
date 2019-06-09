# Object types

`object`, `stdClass`, `Foo`, `Bar\Baz` etc. are examples of object types. These types are also valid types in PHP.

#### Generic object types

Psalm supports using generic object types like `ArrayObject<int, string>`. Any generic object should be typehinted with appropriate [`@template` tags](templated_annotations.md).
