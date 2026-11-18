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

See [Classes only instantiated from the command line](../../security_analysis/avoiding_false_positives.md#classes-only-instantiated-from-the-command-line)
for what is checked, and what is trusted.
