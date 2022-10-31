# UnsupportedReferenceUsage

Emitted when Psalm encounters a reference that it is not currently able to track (for instance a reference to an array
offset of an array offset: `$foo = &$bar[$baz[0]]`). When an unsupported reference is encountered, Psalm will issue this
warning and treat the variable as though it wasn't actually a reference.

## How to fix

This can sometimes be fixed by using a temporary variable:

```php
<?php

/** @var non-empty-list<int> */
$bar = [1, 2, 3];
/** @var non-empty-list<int> */
$baz = [1, 2, 3];

$foo = &$bar[$baz[0]];
```

can be turned into

```php
<?php

/** @var non-empty-list<int> */
$bar = [1, 2, 3];
/** @var non-empty-list<int> */
$baz = [1, 2, 3];

$offset = $baz[0];
$foo = &$bar[$offset];
```
