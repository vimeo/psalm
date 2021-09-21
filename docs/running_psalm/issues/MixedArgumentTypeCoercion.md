# MixedArgumentTypeCoercion

Emitted when Psalm cannot be sure that part of an array/iterable argument's type constraints can be fulfilled

```php
<?php

function foo(array $a) : void {
    takesStringArray($a);
}

/** @param string[] $a */
function takesStringArray(array $a) : void {}
```

This can happen with variadic arguments when `@no-named-arguments` is not specified:

```php
<?php

/** @param list<int> $args */
function foo(int ...$args): array {
    return $args; // $args is array<array-key, int> since it can have named arguments
}
```
