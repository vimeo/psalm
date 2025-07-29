# UndefinedFunction

Emitted when referencing a function that doesn't exist

```php
<?php

foo();
```

Is also emitted when using extension functions not added to the `composer.json` requirements or to the [enableExtensions](https://psalm.dev/docs/running_psalm/configuration/#enableextensions) config key.  