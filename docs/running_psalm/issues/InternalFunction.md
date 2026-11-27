# InternalFunction

Emitted when calling a function marked as internal from an unrelated namespace, or a function marked as
psalm-internal from outside the given namespace.

```php
<?php

namespace A {
    /**
     * @internal
     */
    function foo(): void {
    }
}
namespace B {
    \A\foo();
}
```
