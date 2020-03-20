# AbstractMethodCall

Emitted when an attempt is made to call an abstract static method directly

```php
<?php

abstract class Base {
    abstract static function bar() : void;
}

Base::bar();
```
