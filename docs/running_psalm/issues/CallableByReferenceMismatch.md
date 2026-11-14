# CallableByReferenceMismatch

Emitted when a callable that takes a parameter by reference is passed where the callable is called with a value for it

```php
<?php

/** @param callable(int): void $callback */
function run(callable $callback): void {
    $callback(1);
}

function increment(int &$i): void {
    ++$i;
}

run('increment'); // CallableByReferenceMismatch
```

PHP raises a warning when the callable is called, as a value can't be passed by reference.

## How to fix

Pass a callable that takes the parameter by value, or declare the parameter of the expected callable by reference:

```php
<?php

/** @param callable(int &$i): void $callback */
function run(callable $callback): void {
    $i = 1;
    $callback($i);
}
```
