# PossiblyInvalidDocblockTag

Emitted when Psalm detects a likely mix-up of docblock tags, e.g. `@var`
used on a method (where `@param` is likely expected).

```php
<?php

/** @var int $param */
function foo($param): void {}
```
