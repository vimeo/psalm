# MissingTemplateParam

Emitted when using the `@extends`/`@implements` annotation to extend a class without
extending all its template params.

```php
<?php

/**
 * @template-implements ArrayAccess<int>
 */
class SomeIterator implements ArrayAccess
{
    public function offsetSet($offset, $value) {
    }

    public function offsetExists($offset) {
        return false;
    }

    public function offsetUnset($offset) {
    }

    public function offsetGet($offset) {
        return null;
    }
}
```

Trailing template params with a [default](../../annotating_code/templated_annotations.md#template-defaults)
may be left out, so `@extends Factory<User>` is fine below. The others still have to be given,
and the issue then says how many are needed at least:

```php
<?php

/**
 * @template TModel of object
 * @template TCount of int|null = null
 */
abstract class Factory {}

final class UserFactory extends Factory {}
```
