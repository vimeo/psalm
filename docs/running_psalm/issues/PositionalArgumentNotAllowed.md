# PositionalArgumentNotAllowed

Emitted when an argument is passed by position to a function with `@arguments-by-name`, or to an optional parameter of a function with `@optional-arguments-by-name`.

```php
<?php

/** @optional-arguments-by-name */
function connect(string $host, int $port = 5432, bool $persistent = false): void {
}

connect("localhost", 5433);

```

## Why this is bad

These annotations indicate that the names of the parameters are stable but their order is not. An update may add, move or remove an optional parameter, and break function calls that pass this argument by position.

## How to fix

Pass the argument by name.

```php
<?php

/** @optional-arguments-by-name */
function connect(string $host, int $port = 5432, bool $persistent = false): void {
}

connect("localhost", port: 5433);

```
