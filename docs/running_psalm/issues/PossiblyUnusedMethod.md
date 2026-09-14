# PossiblyUnusedMethod

Emitted when `--find-dead-code` is turned on and Psalm cannot find any calls to
a public or protected method.

If this method is genuinely part of your public API — used from outside the analysed codebase (for example a library entry point, or a method invoked only by a framework) — annotate the containing class with `@api` (or `@psalm-api`); use this only for real public surface. Otherwise the method is dead code and should be removed.

See [`@api`](../../annotating_code/supported_annotations.md#api-psalm-api) for the full guidance; note that `@psalm-suppress` is not a substitute for `@api`, as it silences the report without marking the method as used.

```php
<?php

class A {
    public function foo() : void {}
    public function bar() : void {}
}
(new A)->foo();
```
