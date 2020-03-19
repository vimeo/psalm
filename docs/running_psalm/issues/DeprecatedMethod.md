# DeprecatedMethod

Emitted when calling a deprecated method on a given class:

```php
class A {
    /** @deprecated */
    public function foo() : void {}
}
(new A())->foo();
```
