# PositionalArgumentNotAllowed

Emitted when a positional argument is used when calling a function with `@only-named-arguments`.

```php
<?php

/** @only-named-arguments */
function foo(int $a, int $b): int {
	return $a + $b;
}

foo(0, 1);

```

## Why this is bad

The `@only-named-arguments` annotation indicates that the parameter order may change in the future, and an update may break backwards compatibility with function calls using positional arguments.

## How to fix

Use named arguments when calling functions annotated with `@only-named-arguments`.

```php
<?php

/** @only-named-arguments */
function foo(int $a, int $b): int {
	return $a + $b;
}

foo(a: 0, b: 1);

```
