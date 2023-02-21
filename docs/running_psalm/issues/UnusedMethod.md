# UnusedMethod

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a
given private method or function.

If this method is used and part of the public API, annotate the containing class
with `@psalm-api`.

```php
<?php

class A {
    public function __construct() {
        $this->foo();
    }
    private function foo() : void {}
    private function bar() : void {}
}
$a = new A();
```
