# MixedArrayAccess

Emitted when trying to access an array offset on a value whose type Psalm cannot determine

```php
<?php

echo $GLOBALS['foo'][0];
```
