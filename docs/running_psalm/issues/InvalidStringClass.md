# InvalidStringClass

Emitted when `allowStringToStandInForClass="false"` and a string literal is used as
an actual class name in a dynamic class expression or compared with a class string.
Use `Foo::class` instead. Class-string arguments and return values also use
ordinary type-checking diagnostics.

```php
<?php

class Foo {}
$a = "Foo";
new $a();
```
