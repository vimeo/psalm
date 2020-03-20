# UndefinedPropertyFetch

Emitted when getting a property on an object that doesn’t have that property defined

```php
<?php

class A {}
$a = new A();
echo $a->foo;
```
