# ImplementedParamTypeMismatch

Emitted when a class that inherits another, or implements an interface, has docblock param type that's entirely different to the parent. Subclasses of the parent return type are permitted, in docblocks.

```php
class D {
    /** @param string $a */
    public function foo($a): void {}
}

class E extends D {
    /** @param int $a */
    public function foo($a): void {}
}
```
