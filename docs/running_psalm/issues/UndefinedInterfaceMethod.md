# UndefinedInterfaceMethod

Emitted when calling a method that doesn’t exist on an interface

```php
<?php

interface I {}

function foo(I $i) {
    $i->bar();
}
```
