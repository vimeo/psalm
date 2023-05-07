# InvalidExtendClass

Emitted when attempting to extend a final class, a class annotated with `@final` or a class using @psalm-inheritors and not in the inheritor list

```php
<?php

final class A {}

class B extends A {}

/**
 * @final
 */
class DoctrineA {}

class DoctrineB extends DoctrineA {}

/**
 * @psalm-inheritors A|B
 */
class C {}

class D extends C {}
```