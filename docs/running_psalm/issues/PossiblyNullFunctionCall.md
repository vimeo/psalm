# PossiblyNullFunctionCall

Emitted when trying to call a function on a value that may be null

```php
<?php

function foo(?callable $a) : void {
    $a();
}
```

## Further resources

- [CWE-476](https://cwe.mitre.org/data/definitions/476.html)
