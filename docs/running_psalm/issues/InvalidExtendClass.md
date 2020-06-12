# InvalidExtendClass

Emitted when attempting to extends a final class or annotated as final.

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
