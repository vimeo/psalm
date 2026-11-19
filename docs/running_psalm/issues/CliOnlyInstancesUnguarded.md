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

- be final, so that no subclass adds a constructor or an `__unserialize()` of its own;
- not implement `Serializable`, whose `unserialize()` runs on an object created without the constructor;
- declare a constructor, and also `__unserialize()` if the class or a parent has one, or `__wakeup()` otherwise. A
  trait the class uses can declare them too;
- start each of these methods with the guard.

The guard is the first statement of the method. It must throw, or leave `PHP_SAPI` narrowed to `'cli'`, like
`if (PHP_SAPI !== 'cli') { throw ...; }` or a call to a function
[asserting](../../annotating_code/adding_assertions.md#asserting-the-sapi) `'cli' PHP_SAPI`. It must not use `$this`,
directly, through `self::`, `static::` or `parent::`, or in a closure that binds it.

A promoted constructor property can't have hooks, since hooks run before the constructor body. Instead of starting with
the guard, `__wakeup()` can also always throw, which forbids unserializing the class.

## What is trusted

Psalm checks the class where it analyzes it, so a class in a stub, or in a dependency Psalm doesn't analyze, is
trusted. So is what Psalm can't see:

- an object created without its constructor through Reflection (`newInstanceWithoutConstructor()`, lazy objects);
- an extension calling `__set()` before the constructor (PDO's `FETCH_CLASS` without `FETCH_PROPS_LATE`);
- another object, unserialized together with an instance, that calls the instance from its own `__wakeup()` or
  `__unserialize()` before the instance's guard has run. Unserializing data a client can change is already reported as
  [TaintedUnserialize](TaintedUnserialize.md).

See [Command-line code](../../security_analysis/index.md#command-line-code) for what counts as command-line code.
