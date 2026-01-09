# UnevaluatedCode

Emitted when `--find-unused-code` is turned on and Psalm encounters code that will not be evaluated

```php
<?php

function foo() : void {
    return;
    $a = "foo";
}
```
