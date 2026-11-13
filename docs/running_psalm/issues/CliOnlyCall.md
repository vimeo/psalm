# CliOnlyCall

Emitted when code that may run outside the command line calls a function, method or class marked `@psalm-cli-only`.

```php
<?php

/**
 * @psalm-cli-only
 */
final class ImportCommand
{
    public function run(): void
    {
        echo 'Importing ' . (string) ($_SERVER['argv'][1] ?? '') . "\n";
    }
}

function handleRequest(): void
{
    (new ImportCommand())->run();
}
```

## Why this is bad

What `@psalm-cli-only` code prints isn't reported as `TaintedHtml`, because it goes to a terminal. Called from a web request, it goes to the HTTP response instead.

## How to fix

Mark the caller `@psalm-cli-only` too, or check the SAPI first. The check covers the code it guards, like any other assertion:

```php
<?php

/**
 * @psalm-cli-only
 */
function import(): void {}

/**
 * @psalm-cli-only
 */
function run(): void
{
    import(); // no issue
}

if (PHP_SAPI === 'cli') {
    import(); // no issue
}

import(); // CliOnlyCall

if (php_sapi_name() !== 'cli') {
    exit(1);
}

import(); // no issue
array_map(fn(string $file) => import(), ['a.csv']); // no issue
```

A guard function or method works the same when it asserts `PHP_SAPI` (`@psalm-assert`, `@psalm-assert-if-true` or
`@psalm-assert-if-false`; `php_sapi_name()` works as the target too):

```php
<?php

/**
 * @psalm-cli-only
 */
function import(): void {}

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
 * @psalm-assert-if-true 'cli' PHP_SAPI
 */
function isCli(): bool
{
    return PHP_SAPI === 'cli';
}

if (isCli()) {
    import(); // no issue
}

requireCli();
import(); // no issue
```

Code that holds an instance of a class with
[`@psalm-cli-only-instances`](../../security_analysis/avoiding_false_positives.md#classes-only-instantiated-from-the-command-line)
already runs from the command line: calling a method that isn't static of such a class, or a function-like whose
parameter requires an instance of it, reports no `CliOnlyCall`:

```php
<?php

/**
 * @psalm-cli-only-instances
 */
final class ConsoleRequest
{
    public function __construct()
    {
        if (PHP_SAPI !== 'cli') {
            throw new LogicException('Run it from the command line');
        }
    }

    public function __wakeup(): void
    {
        if (PHP_SAPI !== 'cli') {
            throw new LogicException('Run it from the command line');
        }
    }
}

/**
 * @psalm-cli-only
 */
function import(ConsoleRequest $request): void {}

function handle(?ConsoleRequest $request): void
{
    if ($request !== null) {
        import($request); // no issue
    }
}
```
