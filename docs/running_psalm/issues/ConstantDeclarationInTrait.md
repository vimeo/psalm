# ConstantDeclarationInTrait

Emitted when a trait declares a constant in PHP <8.2.0

```php
<?php

trait A {
    const B = 0;
}
```

## Why this is bad

A fatal error will be thrown.
