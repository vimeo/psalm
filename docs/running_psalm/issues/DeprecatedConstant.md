# DeprecatedConstant

Emitted when referring to a deprecated constant:

```php
<?php

class A {
    /** @deprecated */
    const FOO = 'foo';
}

echo A::FOO;
```

## How to fix

Don’t use the deprecated constant.
