# UnusedReturnValue

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a private method’s return value.

```php
<?php

class A {
    public function __construct() {
        $this->foo();
    }
    private function foo() : string {
        return "hello";
    }
}

new A();
```
