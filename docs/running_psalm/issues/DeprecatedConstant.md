# DeprecatedConstant

Emitted when referring to a deprecated constant or enum case:

```php
<?php

class A {
    /** @deprecated */
    const FOO = 'foo';
}

echo A::FOO;

enum B {
    /** @deprecated */
    case B;
}

echo B::B;
```

Global constants are deprecated the same way, or with `#[\Deprecated]` since PHP 8.5:

```php
<?php

#[\Deprecated]
const RETRIES = 3;

echo RETRIES; // DeprecatedConstant
```

Psalm also reports the builtin constants PHP deprecates, whatever PHP version you analyse:

```php
<?php

$names = ['b', 'a'];
sort($names, SORT_LOCALE_STRING); // DeprecatedConstant: use Collator::sort() instead
```

## Why this is bad

The `@deprecated` tag is normally indicative of code that will stop working in the near future.

## How to fix

Don’t use the deprecated constant or enum case
