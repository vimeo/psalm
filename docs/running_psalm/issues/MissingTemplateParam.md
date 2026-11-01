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

Trailing templates with a [default](../../annotating_code/templated_annotations.md#template-defaults) can be left out:
`@extends Factory<User>` would be enough below. Without it, the message gives the minimum count (`expecting at least 1`):

```php
<?php

/**
 * @template TModel of object
 * @template TCount of int|null = null
 */
abstract class Factory {}

final class UserFactory extends Factory {}
```
