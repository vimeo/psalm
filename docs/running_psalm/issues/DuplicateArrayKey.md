# DuplicateArrayKey

Emitted when an array has a key more than once

```php
<?php

$arr = [
    'a' => 1,
    'b' => 2,
    'c' => 3,
    'c' => 4,
];
```

## How to fix

Remove the offending duplicates:

```php
<?php

$arr = [
    'a' => 1,
    'b' => 2,
    'c' => 4,
];
```

The first matching `'c'` key was removed to prevent a change in behaviour (any new duplicate keys overwrite the values of prevvious ones).
