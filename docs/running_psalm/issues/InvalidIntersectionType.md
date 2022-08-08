# InvalidIntersectionType

Emitted when an intersection type is invalid.

```php
<?php

class Foo {}
class Bar {}
class Baz {
    private Foo&Bar $foobar;
}
```