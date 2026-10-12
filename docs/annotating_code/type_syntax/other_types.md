# Other types

- `iterable` - represents the [iterable pseudo-type](https://php.net/manual/en/language.types.iterable.php). Like arrays, iterables can have type parameters e.g. `iterable<string, Foo>`, and a purity in brackets, what iterating over them may do: `iterable[pure]<string, Foo>` accepts arrays and `Traversable[pure]<string, Foo>` (see [Iterators and generators](../purity_model.md#iterators-and-generators)).
- `void` - can be used in a return type when a function does not return a value.
- `resource` represents a [PHP resource](https://www.php.net/manual/en/language.types.resource.php).
- `closed-resource` represents a [PHP resource](https://www.php.net/manual/en/language.types.resource.php) that was closed (using `fclose` or another closing function).
