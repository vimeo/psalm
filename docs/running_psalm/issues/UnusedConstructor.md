# UnusedConstructor

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a given private constructor or function

```php
<?php

class A {
    private function __construct() {}

    public static function createInstance() : void {}
}
A::createInstance();
```
