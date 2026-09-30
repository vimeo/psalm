# UnusedFunction

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a
given free (non-method) function.

If this function is genuinely part of your public API — used from outside the analysed codebase (for example a library entry point, or a function invoked only by a framework or plugin loader) — annotate it with `@api` (or `@psalm-api`); use this only for real public surface. Otherwise the function is dead code and should be removed.

See [`@api`](../../annotating_code/supported_annotations.md#api-psalm-api) for the full guidance; note that `@psalm-suppress` is not a substitute for `@api`, as it silences the report without marking the function as used.

```php
<?php

function foo(): void {}
function bar(): void {}

foo();
```
