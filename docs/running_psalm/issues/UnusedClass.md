# UnusedClass

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a
given class.

If this class is genuinely part of your public API — used from outside the analysed codebase (for example a library entry point, or a class instantiated only by a framework or plugin loader) — annotate it with `@api` (or `@psalm-api`); use this only for real public surface. Otherwise the class is dead code and should be removed.

See [`@api`](../../annotating_code/supported_annotations.md#api-psalm-api) for the full guidance; note that `@psalm-suppress` is not a substitute for `@api`, as it silences the report without marking the class as used.

```php
<?php

final class A {}
final class B {}
$a = new A();
```
