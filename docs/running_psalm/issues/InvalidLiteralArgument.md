# InvalidLiteralArgument

Emitted when a scalar value is passed to a method that expected another scalar type

```php
<?php

function foo(string $s) : void {
    echo strpos(".", $s);
}
```
