# UndefinedDocblockClass

Emitted when referencing a class that doesn’t exist from a docblock

```php
<?php

/**
 * @param DoesNotExist $a
 */
function foo($a) : void {}
```
