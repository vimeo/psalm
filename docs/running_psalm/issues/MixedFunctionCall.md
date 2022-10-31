# MixedFunctionCall

Emitted when calling a function on a value whose type Psalm cannot infer.

```php
<?php

/** @var mixed */
$a = $GLOBALS['foo'];
$a();
```
