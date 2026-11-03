# StringIncrement

Emitted when incrementing a non-numeric string (`$a = "a"; $a++;` gives `"b"`). This works in PHP,
but is unexpected behaviour for most people, and it is deprecated as of PHP 8.5: use
[`str_increment()`](https://www.php.net/manual/en/function.str-increment.php) (available since
PHP 8.3) instead. The issue is emitted whatever the PHP version the code is analysed for.

```php
<?php

$a = "hello";
$a++;
```

## How to fix

```php
<?php

$a = "hello";
$a = str_increment($a);
```
