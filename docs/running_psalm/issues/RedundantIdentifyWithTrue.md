# RedundantIdentityWithTrue

Emitted when comparing a known boolean with true

```php
<?php

function returnsABool(): bool {
    return rand(1, 2) === 1;
}

if (returnsABool() === true) {
    echo "hi!";
}
```
