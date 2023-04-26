# Trace

Not really an issue. Just reports the type of a variable when using
[`@psalm-trace`](../annotating_code/supported_annotations.md#psalm-trace).

```php
<?php

/** @psalm-trace $x */
$x = getmypid();
```

## How to fix

Use it for debugging purposes, not for production
