# MissingOverrideAttribute

Emitted when the config flag `ensureOverrideAttribute` is set to `true` and a method on a child class or interface overrides a method on a parent, but no `Override` attribute is present.

```php
<?php

class A {
    function receive(): void
    {
    }
}

class B extends A {
    function receive(): void
    {
    }
}
```

## Why this is bad

Having an `Override` attribute on overridden methods makes intentions clear. Read the [PHP RFC](https://wiki.php.net/rfc/marking_overriden_methods) for more details.
