# NonVariableReferenceReturn

Emitted when a function returns by reference expression that is not a variable

```php
<?php

function &getByRef(): int {
    return 5;
}
```
