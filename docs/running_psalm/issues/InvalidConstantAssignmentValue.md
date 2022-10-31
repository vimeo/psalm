# InvalidConstantAssignmentValue

Emitted when attempting to assign a value to a class constant that cannot contain that type.

```php
<?php

class Foo {
    /** @var int */
    public const BAR = "bar";
}
```
