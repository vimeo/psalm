# UndefinedPropertyAssignment

Emitted when assigning a property on an object that doesn’t have that property defined

```php
<?php

class A {}
$a = new A();
$a->foo = "bar";
```
