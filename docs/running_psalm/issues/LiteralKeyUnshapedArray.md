# LiteralKeyUnshapedArray

Emitted when a literal key is used on an unshaped array, enabled by the [disallowLiteralKeysOnUnshapedArrays](https://psalm.dev/docs/running_psalm/configuration/#disallowliteralkeysonunshapedarrays) config parameter.  

Useful to enforce usage of [shaped arrays](https://psalm.dev/docs/annotating_code/type_syntax/array_types/#object-like-arrays) instead of [generic arrays](https://psalm.dev/docs/annotating_code/type_syntax/array_types/#object-like-arrays).  

```php
<?php

/**
 * @param array<string, bool> $arr
 */
function takesGenericArr(array $arr): void {
    // Error: LiteralKeyUnshapedArray
    $flagA = $arr['flagA'];
    // Error: LiteralKeyUnshapedArray
    $flagB = $arr['flagB'];
}

/**
 * @param array{flagA: bool, flagB: bool} $arr
 */
function takesShapedArr(array $arr): void {
    // OK
    $flagA = $arr['flagA'];
    // OK
    $flagB = $arr['flagB'];
}
```
