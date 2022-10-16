# MixedAssignment

Emitted when assigning an unannotated variable to a value for which Psalm
cannot infer a type more specific than `mixed`.

```php
<?php

$a = $GLOBALS['foo'];
```

## How to fix

The above example can be fixed in a few ways – by adding an `assert` call:

```php
<?php

$a = $GLOBALS['foo'];
assert(is_string($a));
```

or by adding an explicit cast:

```php
<?php

$a = (string) $GLOBALS['foo'];
```

or by adding a docblock

```php
<?php

/** @var string */
$a = $GLOBALS['foo'];
```
