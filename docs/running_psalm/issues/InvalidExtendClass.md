# InvalidExtendClass

Emitted when attempting to extend a final class or a class annotated with `@final`.

```php
<?php

final class A {}

class B extends A {}

/**
 * @final
 */
class DoctrineA {}

class DoctrineB extends DoctrineA {}
```
