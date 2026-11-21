# DeprecatedSyntax

Emitted when code uses a statement or declaration that PHP deprecates, whatever PHP version you analyse:

```php
<?php

function load(): string {
    try {
        return (string) file_get_contents('a.txt');
    } finally {
        return ''; // DeprecatedSyntax: discards the exception and the value returned in try
    }
}
```

It covers:

```php
<?php

final class Connection
{
    const namespace = 'db'; // DeprecatedSyntax: rename it

    public function __construct()
    {
        yield 1; // DeprecatedSyntax: a constructor can't be a generator
    }

    public function __destruct()
    {
        return true; // DeprecatedSyntax: a destructor can't return a value
    }

    /** @return array<string, string>|null */
    public function __debugInfo(): ?array // DeprecatedSyntax: declare array
    {
        return null; // DeprecatedSyntax: return []
    }

    public function connect(DateTimeZone $zone = null, int $retries = 1, string $dsn): void {} // DeprecatedSyntax: ?DateTimeZone, no default before $dsn

    public function __wakeup(): void {} // DeprecatedSyntax: __unserialize()
}

final class Session
{
    public function __serialize(): array { return []; }

    public function __sleep(): array { return []; } // no issue: serialize() calls __serialize()
}

function &nothing(): void {} // DeprecatedSyntax: a void function returns no reference

$id = (integer) $argv[1]; // DeprecatedSyntax: (int)
$files = `ls`; // DeprecatedSyntax: shell_exec()
$greeting = "Hello ${id}"; // DeprecatedSyntax: "{$id}"
$inverse = 0 ** -1; // DeprecatedSyntax: fpow()

switch ($id) {
    case 1; // DeprecatedSyntax: case 1:
        break;
}
```

## How to fix

Write the replacement each comment gives: move the `return` out of the `finally` block, rename the constant, and drop the value or the `yield`.
