# DeprecatedFunction

Emitted when calling a deprecated function:

```php
<?php

/** @deprecated */
function foo() : void {}
foo();
```

Psalm also reports the builtin functions PHP deprecates, and the arguments it deprecates, whatever PHP version you
analyse:

```php
<?php

function describe(mixed $value, object $object): string {
    $float = is_double($value); // DeprecatedFunction: use is_float() instead
    $id = spl_object_hash($object); // DeprecatedFunction: use spl_object_id() instead
    $query = http_build_query($object); // DeprecatedFunction: pass an array
    return $id . $query . ($float ? 'f' : '');
}

define('DEBUG', true, false); // DeprecatedFunction: leave out the third argument
```

## Why this is bad

The `@deprecated` tag is normally indicative of code that will stop working in the near future.

## How to fix

Don’t use the deprecated function.
