# InaccessibleProperty

Emitted when attempting to access a protected/private property from outside its available scope

```php
<?php

class A {
    /** @return string */
    protected $foo;
}
echo (new A)->foo;
```

It is also emitted when writing to a property whose asymmetric set visibility (PHP 8.4) is not accessible
from the current context:

```php
<?php

class A {
    public private(set) string $foo = 'bar';
}

$a = new A();
$a->foo = 'baz';
```
