# NamedArgumentNotAllowed

Emitted when a named argument is used when calling a function with `@no-named-arguments`.

```php
<?php

/** @no-named-arguments */
function foo(int $a, int $b): int {
	return $a + $b;
}

foo(a: 0, b: 1);

```

## Why this is bad

The `@no-named-arguments` annotation indicates that argument names may be changed in the future, and an update may break backwards compatibility with function calls using named arguments.

## How to fix

Avoid using named arguments for functions annotated with `@no-named-arguments`.

```php
<?php

/** @no-named-arguments */
function foo(int $a, int $b): int {
	return $a + $b;
}

foo(0, 1);

```
