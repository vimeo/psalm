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
