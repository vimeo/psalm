# TraitMethodSignatureMismatch

Emitted when a method's signature or return type differs from corresponding trait-defined method

```php
<?php // phpVersion="7.4"

trait T {
    abstract public function foo(int $i);
}

class A {
    use T;

    public function foo(string $s) : void {}
}
```
