# ForbiddenEcho

Emitted when Psalm encounters an echo statement and the `forbidEcho` flag in your config is set to `true`
This issue is deprecated and will be removed in Psalm 5. Adding echo to forbiddenFunctions in config will result in ForbiddenCode issue instead
```php
<?php

echo("bah");
```
