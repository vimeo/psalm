# InvalidScalarArgument

Emitted when a scalar value is passed to a method that expected another scalar type.

This is only emitted in situations where Psalm can be sure that PHP tries to coerce one scalar type to another.

In all other cases `InvalidArgument` is emitted.

```php
<?php

function foo(int $i) : void {}
function bar(string $s) : void {
    if (is_numeric($s)) {
        foo($s);
    }
}
```
