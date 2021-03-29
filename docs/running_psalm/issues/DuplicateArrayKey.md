# DuplicateArrayKey

Emitted when an array has a key more than once

```php
<?php

$arr = [
    'a' => 'one',
    'b' => 'two',
    'c' => 'this text will be overwritten by the next line',
    'c' => 'three',
];
```

This can be caused by variadic arguments if `@no-named-arguments` is not specified:

```php
<?php
function foo($bar, ...$baz): array
{
    return [$bar, ...$baz]; // $baz is array<array-key, mixed> since it can have named arguments
}
```

## How to fix

Remove the offending duplicates:

```php
<?php

$arr = [
    'a' => 'one',
    'b' => 'two',
    'c' => 'three',
];
```

The first matching `'c'` key was removed to prevent a change in behaviour (any new duplicate keys overwrite the values of previous ones).
