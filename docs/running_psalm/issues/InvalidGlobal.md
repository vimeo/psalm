# InvalidGlobal

Emitted when `global` is used in the global scope of a file, where it does nothing: PHP accepts it, but there is no
enclosing function whose scope it could bind to.

```php
<?php

global $e;
```

Inside a function or method it is fine:

```php
<?php

function foo(): void {
    global $e; // no issue
}
```

A file that is only ever included from inside a function (a template, for instance) has `global` statements Psalm
can't see a function around; the issue is then a false positive. See
[Config suppression](../../dealing_with_code_issues/#suppressing-issues) for how to suppress it at the file or
directory level.
