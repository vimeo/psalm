# DeprecatedTrait

Emitted when referring to a deprecated trait:

```php
<?php

/** @deprecated */
trait T {}
class A {
    use T;
}
```

## How to fix

Don’t use the deprecated trait.
