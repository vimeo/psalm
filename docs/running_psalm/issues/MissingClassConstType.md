# MissingClassConstType

Emitted when a class constant doesn't have a declared type.

```php
<?php

class A {
    public const B = 0;
}
```

Correct with:

```php
<?php

class A {
    public const int B = 0;
}
```
