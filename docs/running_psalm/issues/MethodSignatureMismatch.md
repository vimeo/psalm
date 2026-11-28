# MethodSignatureMismatch

Emitted when a method parameter differs from a parent method parameter, or if there are fewer parameters than the parent method

```php
<?php

class A {
    public function foo(int $i) : void {}
}
class B extends A {
    public function foo(string $i) : void {}
}
```

Also emitted when an override raises or adds the lower bound of a method template:

```php
<?php

class Animal {}
class Cat extends Animal {}

class Factory {
    /**
     * @template T
     * @param T $value
     * @return T
     */
    public function make(mixed $value): mixed { return $value; }
}

class CatFactory extends Factory {
    /**
     * @template T super Cat
     * @param T $value
     * @return T
     */
    public function make(mixed $value): mixed { return $value; }
}
```
