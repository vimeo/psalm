# MismatchingDocblockParamType

Emitted when an `@param` entry in a function’s docblock doesn’t match the param typehint

```php
<?php

/**
 * @param int $b
 */
function foo(string $b) : void {}
```
