# InvalidToString

Emitted when a `__toString` method does not always return a `string`

```php
<?php

class A {
    public function __toString() {
        /** @psalm-suppress InvalidReturnStatement */
        return true;
    }
}
```
