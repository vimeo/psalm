# UndefinedMagicPropertyAssignment

Emitted when assigning a property on an object that does not have that magic property defined

```php
<?php

/**
 * @property string $bar
 */
class A {
    /** @param mixed $value */
    public function __set(string $name, $value) {}
}
$a = new A();
$a->foo = "bar";
```

To fix, add all used magic properties as `@property` annotations: 

```php
<?php

/**
 * @property string $bar
 * @property string $foo
 */
class A {
    /** @param mixed $value */
    public function __set(string $name, $value) {}
}
$a = new A();
$a->foo = "bar";
```

Or, **only if** dealing with generic container objects (like `ArrayObject`), use `@psalm-no-seal-properties`.

```php
<?php

/** @psalm-no-seal-properties */
class ArrayBag {
    public function __construct(private array $arr = []) {}
    public function __set(string $k, mixed $v) {
        $this->arr[$k] = $v;
    }
    public function __get(string $k): mixed {
        return $this->arr[$k];
    }
}
$a = new ArrayBag(['bar' => 'foo']);
$a->foo = "bar";
```
