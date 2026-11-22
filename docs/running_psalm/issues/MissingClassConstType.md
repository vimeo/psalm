# MissingClassConstType

Emitted when a class constant doesn't have a declared type. Constants of `final` classes and `final` constants are exempt, since their type can't be changed by an override.

```php
<?php

class A {
    public const B = 0; // MissingClassConstType
}

final class C {
    public const D = 0; // no issue
}

class E {
    final public const F = 0; // no issue
}
```

Correct with:

```php
<?php

class A {
    public const int B = 0;
}
```
