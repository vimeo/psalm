# InheritorViolation

Emitted when a class/interface using `@psalm-inheritors` is extended/implemented
by a class that does not fulfil it's requirements.

```php
<?php

/**
 * @psalm-inheritors FooClass|BarClass
 */
class BaseClass {}
class BazClass extends BaseClass {}
// InheritorViolation is emitted, as BaseClass can only be extended
// by FooClass|BarClass, which is not the case
$a = new BazClass();
```
