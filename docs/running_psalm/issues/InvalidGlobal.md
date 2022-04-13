# InvalidGlobal

Emitted when there's a reference to the global keyword where it's not expected.

```php
<?php

global $e;
```

If the file is included from a non-global scope this issue will have to be suppressed. See
[Config suppression](../dealing_with_code_issues/#suppressing-issues) for how to suppress this at the file or directory
level.
