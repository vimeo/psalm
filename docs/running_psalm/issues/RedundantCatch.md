# RedundantCatch

Emitted when catching an exception that was already caught.

```php
<?php

class A {}
try {
    $worked = true;
} catch (Throwable $e) {
} catch (Exception $e) {
}
```

```php
<?php

try {
    $worked = true;
} catch (Exception|Throwable $e) {
}
```
