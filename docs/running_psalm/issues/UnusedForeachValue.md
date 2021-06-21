# UnusedForeachValue

Emitted when `--find-dead-code` is turned on and Psalm cannot find any
references to the foreach value

```php
<?php

/** @param array<string, int> $a */
function foo(array $a) : void {
    foreach ($a as $key => $value) { // $value is unused
        echo $key;
    }
}
```

Can be suppressed by prefixing the variable name with an underscore or naming
it `$_`:

```php
<?php

foreach ([1, 2, 3] as $key => $_val) {}

foreach ([1, 2, 3] as $key => $_) {}
```
