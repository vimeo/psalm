# PossiblyUndefinedStringArrayOffset

Emitted when the config flag `ensureArrayStringOffsetsExist` is set to `true` and a string-keyed offset is not checked for existence

```php
<?php

/**
 * @param array<string, string> $arr
 */
function foo(array $arr) : void {
    echo $arr["hello"];
}
```
