# MissingDependency

Emitted when referencing a class that doesn’t exist

```php
<?php

/**
 * @psalm-suppress UndefinedClass
 */
class A extends B {}

$a = new A();
```
