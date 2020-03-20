# UndefinedInterface

Emitted when referencing an interface that doesn’t exist but does have an identically-named class.

```php
<?php

class C {}

interface I extends C {}
```
