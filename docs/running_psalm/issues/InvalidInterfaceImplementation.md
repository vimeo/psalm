# InvalidInterfaceImplementation

Emitted when trying to implement interface that cannot be implemented (e.g. `Throwable`, `UnitEnum`, `BackedEnum`).

```php
<?php

class E implements UnitEnum 
{
    public static function cases(): array 
    {
        return []; 
    }
}
```
