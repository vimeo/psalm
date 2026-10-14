# UnusedFunctionCall

Emitted when `--find-dead-code` is turned on and Psalm finds a function call whose return value is not used anywhere

```php
<?php

$a = strlen("hello");
strlen("goodbye"); // unused
echo $a;
```

It is also emitted, regardless of `--find-dead-code`, when the return value of a function annotated with PHP 8.5's `#[\NoDiscard]` attribute is discarded. Casting the call to `(void)` intentionally discards the value without triggering the issue.
