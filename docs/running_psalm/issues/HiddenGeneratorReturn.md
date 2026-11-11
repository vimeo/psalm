# HiddenGeneratorReturn

Emitted when a generator returns a value, but its declared return type (`iterable`, `Iterator`, `Traversable`, …) doesn't mention `Generator`, so the value can't be read back through the declared type.

```php
<?php

/** @return iterable<int, string> */
function gen(): iterable {
    yield 'a';
    return 1; // HiddenGeneratorReturn
}
```

## Why this is bad

A function containing `yield` always returns a `Generator`. The value of `return` is only reachable with `Generator::getReturn()`, which callers can't use on an `iterable`.

## How to fix

Declare a `Generator` return type, whose fourth template parameter is the type of the returned value:

```php
<?php

/** @return Generator<int, string, mixed, int> */
function gen(): Generator {
    yield 'a';
    return 1; // no issue
}
```

Or remove the returned value (`return;` and `return null;` are not reported).
