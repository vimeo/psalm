# PossiblyUnusedMethod

Emitted when `--find-dead-code` is turned on and Psalm cannot find any calls to
a public or protected method.

If this method is used and part of the public API, annotate the containing class
with `@psalm-api`.

```php
<?php

class A {
    public function foo() : void {}
    public function bar() : void {}
}
(new A)->foo();
```
