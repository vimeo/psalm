# MissingExtensionRequirement

Emitted when code uses a function or a class of a PHP extension that `composer.json` doesn't declare.

```php
<?php

function length(string $s): int {
    return mb_strlen($s);
}
```

## Why this is bad

On a PHP without the extension, the code fails with an `Error`, and Composer can't tell whoever installs the project
that it needs the extension.

## How to fix

Add the extension to `require` in `composer.json`, or to `suggest` if the code works without it:

```json
{
    "require": {
        "ext-mbstring": "*"
    },
    "suggest": {
        "ext-igbinary": "Used to serialize the cache"
    }
}
```

Only what fails without the extension is reported, outside of a check that it is loaded:

```php
<?php

function f(Redis $redis, mixed $x, string $s): void {      // no issue: type declarations don't need the class
    new Redis();                                            // MissingExtensionRequirement
    Redis::OPT_PREFIX;                                      // MissingExtensionRequirement
    $redis instanceof Redis;                                // no issue
    Redis::class;                                           // no issue

    if (extension_loaded('mbstring')) {
        mb_strlen($s);                                      // no issue
    }
    if (function_exists('iconv')) {
        iconv('UTF-8', 'ASCII', $s);                        // no issue
    }

    strlen($s); json_encode($x); new DateTime();            // no issue: the core extensions need no declaration
}

final class Client extends SoapClient {}                    // MissingExtensionRequirement
```

An extension is declared when `composer.json` requires it (`require-dev` too) or suggests it, when an installed
package provides or replaces it (the polyfills, such as `symfony/polyfill-mbstring`), and when
[`<enableExtensions>`](../configuration.md#enableextensions) lists it. Without a `composer.json`, the issue isn't
reported. The core extensions (`Core`, `standard`, `date`, `pcre`, `SPL`, `Reflection`, `hash`, `json` from PHP 8.0
and `random` from PHP 8.2) need no declaration.
