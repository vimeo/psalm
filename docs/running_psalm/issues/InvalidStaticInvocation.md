# InvalidStaticInvocation

Emitted when trying to call an instance function statically

```php
class A {
    /** @var ?string */
    public $foo;

    public function bar() : void {
        echo $this->foo;
    }
}

A::bar();
```
