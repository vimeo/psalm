# RedundantIdentityWithTrue

This is a codestyle rule, it does not indicate a possible bug.  

Emitted when comparing a known boolean with with a literal boolean and the `allowBoolToLiteralBoolComparison` flag is set to false.

```php
<?php

function returnsABool(): bool {
    return rand(1, 2) === 1;
}

if (returnsABool() === true) {
    echo "hi!";
}

if (returnsABool() !== false) {
    echo "hi!";
}
```

To fix:

```php
<?php

function returnsABool(): bool {
    return rand(1, 2) === 1;
}

if (returnsABool()) {
    echo "hi!";
}

if (returnsABool()) {
    echo "hi!";
}
```

