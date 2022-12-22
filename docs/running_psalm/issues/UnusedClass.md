# UnusedClass

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a
given class.

If this class is used and part of the public API, annotate it with `@psalm-api`.

```php
<?php

class A {}
class B {}
$a = new A();
```
