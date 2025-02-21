# UndefinedMagicMethod

Emitted when calling a magic method that does not exist

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

To fix, add all used magic methods as `@method` annotations: 

```php
<?php

/**
 * @method bar():string
 * @method foo():string
 */
class A {
    public function __call(string $name, array $args) {
        return "cool";
    }
}
(new A)->foo();
```


Or, **only if** dealing with generic wrapper objects (like `FFI` classes), use `@psalm-no-seal-methods`.  
Try to avoid using `@psalm-no-seal-methods`, as it worsens type coverage, and is not needed in the vast majority of cases.  
