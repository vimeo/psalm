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

## Why this is bad

The `@deprecated` tag is normally indicative of code that will stop working in the near future.

## How to fix

Don’t use the deprecated constant or enum case
