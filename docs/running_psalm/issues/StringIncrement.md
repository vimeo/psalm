# StringIncrement

Emitted when `++` or `--` is applied to a string that may be non-numeric, which PHP deprecates. The issue is emitted
whatever the PHP version the code is analysed for.

```php
<?php

$a = "hello";
$a++;
```

It covers:

```php
<?php

function f(string $id): void {
    $word = "az";
    $word++; // StringIncrement: deprecated since PHP 8.5, use str_increment()
    $empty = "";
    $empty++; // StringIncrement: deprecated since PHP 8.3
    $symbol = "a-1";
    $symbol++; // StringIncrement: deprecated since PHP 8.3, it isn't alphanumeric
    $empty = "";
    $empty--; // StringIncrement: deprecated since PHP 8.3, gives -1
    $id--; // StringIncrement: deprecated since PHP 8.3, has no effect on a non-numeric string
    $number = "7";
    $number--; // no issue
}
```

## How to fix

```php
<?php

$a = "hello";
$a = str_increment($a);
```
