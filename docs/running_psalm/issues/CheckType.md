# CheckType

Checks if a variable matches a specific type.
Similar to [Trace](./Trace.md), but only shows if the type does not match the expected type.

```php
<?php

/** @psalm-check-type $x = 1 */
$x = 2; // Checked variable $x = 1 does not match $x = 2
```


```php
<?php

/** @psalm-check-type-exact $x = int */
$x = 2; // Checked variable $x = int does not match $x = 2
```
