# TaintedExtract

Emitted when user-controlled array can be passed into an `extract` call.

```php
<?php

$array = $_GET;
extract($array);
```
