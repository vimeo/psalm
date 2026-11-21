# CliOnlyInstancesUnguarded

Emitted when a class annotated with `@psalm-cli-only-instances` could still be instantiated outside the command line —
for example, because it isn't `final`, implements `Serializable`, is missing `__construct()` or
`__unserialize()`, or doesn't guard them with a CLI SAPI check.

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

    public function __unserialize(array $data): void
    {
        if (PHP_SAPI !== 'cli') {
            throw new LogicException('Run it from the command line');
        }
    }
}
```

## Why this is bad

Instance methods of a `@psalm-cli-only-instances` class — and any code taking an instance of it — are treated as
command-line code: their output is exempted from `TaintedHtml` and `TaintedTextWithQuotes`, and they can call
`@psalm-cli-only` code. If an instance can be created during a web request, that code could run in the browser too.

## How to fix

Make the class `final`, and put a CLI guard as the first statement of both `__construct()` and `__unserialize()` (or
the `__wakeup()` it replaces), before anything touches `$this`. The guard can be a `PHP_SAPI` / `php_sapi_name()` check that throws or
exits, or a call to a helper asserting `'cli' PHP_SAPI`. Alternatively, `__unserialize()` can always
throw to forbid unserialization altogether:

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

    public function __unserialize(array $data): void
    {
        throw new LogicException('A request cannot be unserialized');
    }
}
```

## What is checked

To satisfy `@psalm-cli-only-instances`, the class must:

- be `final`, so no subclass can add an unguarded constructor or `__unserialize()`;
- not implement `Serializable` (its `unserialize()` runs on an object created without calling the constructor);
- declare `__construct()`, plus `__unserialize()`, or the `__wakeup()` the class or a parent defines without an
  `__unserialize()` — these may come from a trait used by the class;
- avoid property hooks on promoted constructor properties, since hooks execute before the constructor body;
- start each of those methods with a valid CLI guard.

**Guard rules:** The guard must be the method's first statement, and must either always throw/exit or narrow `PHP_SAPI`
to `'cli'` (such as `if (PHP_SAPI !== 'cli') { throw ...; }` or a helper
[asserting](../../annotating_code/adding_assertions.md#asserting-the-sapi) `'cli' PHP_SAPI`). It must not access
`$this`, directly, via `self::` / `static::` / `parent::`, or inside a closure bound to `$this`.

## What is trusted

Psalm can only check classes it analyzes. Classes declared in stubs or unanalyzed dependencies are trusted, as are
bypass paths Psalm cannot see:

- objects instantiated without their constructor via Reflection (`newInstanceWithoutConstructor()`, lazy objects);
- extensions invoking `__set()` before the constructor (such as PDO's `FETCH_CLASS` without `FETCH_PROPS_LATE`);
- another object unserialized alongside the instance that invokes it from its own `__wakeup()` or `__unserialize()`
  before the instance's guard runs (note that unserializing attacker-controlled data is already flagged as
  [TaintedUnserialize](TaintedUnserialize.md)).

See [Command-line code](../../security_analysis/index.md#command-line-code) for what counts as command-line code.
