# PossiblyUnusedReturnValue

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a public/protected method’s return type.

```php
<?php

class A {
    public function foo() : string {
        return "hello";
    }
}
(new A)->foo();
```
