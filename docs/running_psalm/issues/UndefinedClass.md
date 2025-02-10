# UndefinedClass

Emitted when referencing a class that does not exist

```php
<?php

$a = new A();
```

Is also emitted when using extension classes not added to the `composer.json` requirements or to the [enableExtensions](https://psalm.dev/docs/running_psalm/configuration/#enableextensions) config key.  