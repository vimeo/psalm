# UndefinedMagicMethod

Emitted when calling a magic method that doesn’t exist

```php
<?php

/**
 * @method bar():string
 */
class A {
    public function __call(string $name, array $args) {
        return "cool";
    }
}
(new A)->foo();
```
