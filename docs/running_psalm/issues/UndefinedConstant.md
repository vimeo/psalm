# UndefinedConstant

Emitted when referencing a constant that does not exist

```php
<?php

echo FOO_BAR;
```

Is also emitted when using extension constants not added to the `composer.json` requirements or to the [enableExtensions](https://psalm.dev/docs/running_psalm/configuration/#enableextensions) config key.  