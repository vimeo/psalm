# NoEnumProperties

Emitted when there a property defined on an enum, as PHP
does not allow user-defined properties on enums.

```php
<?php

enum Status {
    public $prop;
}
```
