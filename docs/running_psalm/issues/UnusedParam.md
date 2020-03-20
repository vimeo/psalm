# UnusedParam

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a particular parameter in a private method or function

```php
<?php

function foo(int $a, int $b) : int {
    return $a + 4;
}
```
