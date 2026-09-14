# PossiblyUnusedMethod

Emitted when `--find-dead-code` is turned on and Psalm cannot find any calls to
a public or protected method.

If this method is genuinely part of your public API — used from outside the analysed codebase (for example a library entry point, or a method invoked only by a framework) — annotate the containing class with `@api` (or `@psalm-api`) — mark the whole class rather than the individual method, since a public-API method almost always belongs to a public-API class. Otherwise the method is dead code and should be removed: a method reported as unused in a class that is **not** already marked `@api` is almost always genuinely unused, so annotating the individual method with `@api` to silence the report is very rarely the right fix.

See [`@api`](../../annotating_code/supported_annotations.md#api-psalm-api) for the full guidance; note that `@psalm-suppress` is not a substitute for `@api`, as it silences the report without marking the method as used.

```php
<?php

class A {
    public function foo() : void {}
    public function bar() : void {}
}
(new A)->foo();
```
