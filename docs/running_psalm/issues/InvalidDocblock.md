# InvalidDocblock

Emitted when there's an error in a docblock type

```php
<?php

/** @var array() */
$a = [];
```

It is also emitted for a [template default](../../annotating_code/templated_annotations.md#template-defaults) that is not within the bound of its template:

```php
<?php

/**
 * @template T of int = string
 */
final class Box {}
```

or that uses a template declared after it:

```php
<?php

/**
 * @template T = list<U>
 * @template U = int
 */
final class Pair {}
```
