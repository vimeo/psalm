# CliOnlyInstancesUnguarded

Emitted when the instances of a class with `@psalm-cli-only-instances` may exist outside of the command line: the
class isn't final, implements `Serializable`, or doesn't declare its constructor and the method `unserialize()` calls
(`__unserialize()`, or `__wakeup()` without it), or one of them doesn't start with a check that it runs from the
command line.

```php
<?php

/**
 * @psalm-cli-only-instances
 */
final class ConsoleRequest
{
    public function __construct(public string $path)
    {
    }

    public function __wakeup(): void
    {
        if (PHP_SAPI !== 'cli') {
            throw new LogicException('Run it from the command line');
        }
    }
}
```

## Why this is bad

The instance methods of the class, and the code taking an instance of it, count as command-line code: what they print
isn't reported as `TaintedHtml`, and they can call `@psalm-cli-only` code. If an instance can be made in a web request,
that code runs there too.

## How to fix

Make the class final, and start its constructor and its `__unserialize()` or `__wakeup()` with the guard, before
anything uses `$this`: a `PHP_SAPI` or `php_sapi_name()` check that throws or exits, or a call of a function asserting
`'cli' PHP_SAPI`. A `__wakeup()` or `__unserialize()` can also always throw, so that the class can't be unserialized:

```php
<?php

/**
 * @psalm-assert 'cli' PHP_SAPI
 */
function requireCli(): void
{
    if (PHP_SAPI !== 'cli') {
        throw new LogicException('Run it from the command line');
    }
}

/**
 * @psalm-cli-only-instances
 */
final class ConsoleRequest
{
    public function __construct(public string $path)
    {
        requireCli();
    }

    public function __wakeup(): void
    {
        throw new LogicException('A request can not be unserialized');
    }
}
```

## What is checked

The class must:

- be final, so that no subclass adds a constructor or an `__unserialize()` of its own, and not implement
  `Serializable`, whose `unserialize()` runs on an object created without the constructor;
- declare its constructor, and `__unserialize()` if it or a class it extends has one, or `__wakeup()` otherwise (in the
  class itself or a trait it uses);
- start each of them with the guard: their first statement throws, or leaves `PHP_SAPI` narrowed to `'cli'`, like
  `if (PHP_SAPI !== 'cli') { throw ...; }` or a call of a function
  [asserting](../../annotating_code/adding_assertions.md#asserting-the-sapi) `'cli' PHP_SAPI`, and doesn't use `$this`
  (directly, through `self::`, `static::` or `parent::`, or in a closure binding it). A promoted constructor property
  can't have hooks, which run before the body. A `__wakeup()` that always throws forbids unserializing the class
  instead.

This is checked where the class is analyzed: a class in a stub or in a dependency Psalm doesn't analyze is trusted.
What Psalm can't see is trusted too: an object made without its constructor through Reflection
(`newInstanceWithoutConstructor()`, lazy objects), an extension calling `__set()` before the constructor (PDO's
`FETCH_CLASS` without `FETCH_PROPS_LATE`), or another object unserialized with an instance calling it from its own
`__wakeup()` or `__unserialize()` before the guard of the instance ran (unserializing data a client can change is
reported as [TaintedUnserialize](TaintedUnserialize.md) already).

See [Command-line code](../../security_analysis/index.md#command-line-code) for what counts as command-line code.
