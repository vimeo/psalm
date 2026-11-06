# WrongCaseFunction

Emitted when a function is called with a different casing than the one it is declared with

```php
<?php

function fooBar(): void {}

foobar();
```

PHP matches function names case-insensitively, so the call works. The issue exists for consistency with [InvalidClass](InvalidClass.md), which does the same for classes.
