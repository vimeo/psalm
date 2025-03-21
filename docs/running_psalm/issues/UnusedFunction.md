# UnusedFunction

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a
given function.

If this class is used and part of the public API, annotate it with `@psalm-api`.

```php
<?php

function a() {}
function b() {}
$a = a();
```
