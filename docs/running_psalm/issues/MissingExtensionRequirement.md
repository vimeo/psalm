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
reported.

The extensions PHP is always built with need no declaration: `Core`, `standard`, `date`, `pcre`, `SPL`, `Reflection`,
`hash` from PHP 7.4, `json` from PHP 8.0, `random` from PHP 8.2, `uri` and `Zend OPcache` from PHP 8.5. Psalm checks
the lowest PHP version the `php` requirement of `composer.json` allows:

```php
<?php // composer.json requires "php": "^7.3"

function digest(string $s): string {
    return hash('sha256', $s);                              // MissingExtensionRequirement: PHP 7.3 can be built without it
}
```
