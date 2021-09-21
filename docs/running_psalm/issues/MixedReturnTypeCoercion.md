# MixedReturnTypeCoercion

Emitted when Psalm cannot be sure that part of an array/iterable return type's constraints can be fulfilled

```php
<?php

/**
 * @return string[]
 */
function foo(array $a) : array {
    return $a;
}
```

This can happen with variadic arguments when `@no-named-arguments` is not specified:

```php
<?php

/** @return list<int> */
function foo(int ...$args): array {
    return $args; // $args is array<array-key, int> since it can have named arguments
}
```
