# PossiblyNullArrayAccess

Emitted when trying to access an array offset on a possibly null value

```php
<?php

function foo(?array $a) : void {
    echo $a[0];
}
```

## Further resources

- [CWE-476](https://cwe.mitre.org/data/definitions/476.html)
