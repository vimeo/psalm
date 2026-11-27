# UndefinedClass

Emitted when referencing a class that does not exist

```php
<?php

$a = new A();
```

Is also emitted for a class of an extension Psalm has no stub for, when the PHP running Psalm doesn't load the extension. The use of an extension `composer.json` doesn't declare is reported as [MissingExtensionRequirement](MissingExtensionRequirement.md).