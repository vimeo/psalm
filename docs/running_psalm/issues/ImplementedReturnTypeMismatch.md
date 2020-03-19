# ImplementedReturnTypeMismatch

Emitted when a class that inherits another, or implements an interface, has docblock return type that's entirely different to the parent. Subclasses of the parent return type are permitted, in docblocks.

```php
class A {
    /** @return bool */
    public function foo() {
        return true;
    }
}
class B extends A {
    /** @return string */
    public function foo()  {
        return "hello";
    }
}
```
