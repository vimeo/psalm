# InvalidOverride

Emitted when an `Override` attribute was added to a method that does not override a method from a parent class or implemented interface.

```php
<?php

class A {
    function receive(): void
    {
    }
}

class B extends A {
    #[Override]
    function recieve(): void
    {
    }
}
```

## Why this is bad

A fatal error will be thrown.
