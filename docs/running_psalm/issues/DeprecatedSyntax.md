# DeprecatedSyntax

Emitted when code uses a statement or declaration that PHP deprecates, whatever PHP version you analyse:

```php
<?php

function load(): string {
    try {
        return file_get_contents('a.txt') ?: '';
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
        return null;
    }
}
```

## How to fix

Move the `return` out of the `finally` block, rename the constant, and drop the value or the `yield`.
